const crypto = require('crypto');
const functions = require('@google-cloud/functions-framework');
const { Firestore } = require('@google-cloud/firestore');
const { Storage } = require('@google-cloud/storage');
const { CloudTasksClient } = require('@google-cloud/tasks');
const { GoogleGenAI } = require('@google/genai');
const { GoogleAuth, Impersonated } = require('google-auth-library');

const storage = new Storage();
const tasksClient = new CloudTasksClient();
const firestoreClients = new Map();

const JOBS_COLLECTION = process.env.JOBS_COLLECTION || 'fcrs_jobs';
const DEFAULT_RESULT_URL_TTL_SECONDS = 900;

const TEMPLATES = {
  product_ad: {
    prompt:
      'Create a premium commercial product advertisement using the uploaded image as the main reference. Keep the uploaded subject recognizable. High detail, studio lighting, clean composition, realistic reflections, premium advertising style.'
  },
  character_portrait: {
    prompt:
      'Create a polished portrait based on the uploaded image. Preserve identity, facial traits, hairstyle, and overall likeness. High detail, premium look, professional lighting.'
  },
  social_media: {
    prompt:
      'Create a high-converting social media creative based on the uploaded image. Keep the subject recognizable, visually strong, clean composition, premium quality, modern ad aesthetic.'
  }
};

const ALLOWED_ASPECT_RATIOS = new Set([
  '1:1',
  '2:3',
  '3:2',
  '3:4',
  '4:3',
  '4:5',
  '5:4',
  '9:16',
  '16:9',
  '21:9'
]);
const ALLOWED_IMAGE_SIZES = new Set(['1K', '2K', '4K']);
const ALLOWED_OUTPUT_MIME_TYPES = new Set(['image/png', 'image/jpeg']);

class RequestError extends Error {
  constructor(status, message, details = '') {
    super(message);
    this.name = 'RequestError';
    this.status = status;
    this.details = typeof details === 'string' && details.trim()
      ? details.trim()
      : '';
  }
}

function normalizeModelDetail(text) {
  if (typeof text !== 'string') {
    return '';
  }

  const normalizedText = text.replace(/\s+/g, ' ').trim();

  if (!normalizedText) {
    return '';
  }

  return normalizedText.length > 1400
    ? `${normalizedText.slice(0, 1397)}...`
    : normalizedText;
}

function buildFinalPrompt(template, userPrompt, hasImage) {
  const imageContext = hasImage
    ? ''
    : '\nNo reference image was provided. Generate the full scene from the text instructions only.';
  const normalizedPrompt = typeof userPrompt === 'string'
    ? userPrompt.trim()
    : '';

  if (template) {
    const templateData = TEMPLATES[template];

    if (!templateData) {
      throw new RequestError(400, `Template invalido: ${template}`);
    }

    const extra = normalizedPrompt
      ? `\nAdditional user instruction: ${normalizedPrompt}`
      : '';

    return `${templateData.prompt}${imageContext}${extra}`;
  }

  if (!normalizedPrompt) {
    throw new RequestError(400, 'Envie um prompt quando nenhum template for usado.');
  }

  return `${normalizedPrompt}${imageContext}`;
}

function buildModelParts(finalPrompt, referenceImages) {
  const parts = [{ text: finalPrompt }];

  if (Array.isArray(referenceImages)) {
    for (const referenceImage of referenceImages) {
      if (!referenceImage?.data || !referenceImage?.mimeType) {
        continue;
      }

      parts.push({
        inlineData: {
          mimeType: referenceImage.mimeType,
          data: referenceImage.data
        }
      });
    }
  }

  return parts;
}

async function getImpersonatedAccessToken() {
  const sourceAuth = new GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/cloud-platform'],
  });

  const sourceClient = await sourceAuth.getClient();

  const targetClient = new Impersonated({
    sourceClient,
    targetPrincipal: process.env.TARGET_SERVICE_ACCOUNT,
    targetScopes: ['https://www.googleapis.com/auth/cloud-platform'],
    lifetime: 3600,
  });

  const accessTokenResponse = await targetClient.getAccessToken();
  return accessTokenResponse.token || accessTokenResponse;
}

function validateSharedSecret(req) {
  const receivedSecret = req.get('X-Site-Secret');
  const expectedSecret = process.env.SITE_SHARED_SECRET;

  if (!expectedSecret) {
    throw new RequestError(500, 'SITE_SHARED_SECRET nao configurado no ambiente.');
  }

  return receivedSecret && receivedSecret === expectedSecret;
}

function validateInternalTaskSecret(req) {
  const receivedSecret = req.get('X-Internal-Task-Secret');
  const expectedSecret = process.env.INTERNAL_TASK_SECRET;

  if (!expectedSecret) {
    throw new RequestError(500, 'INTERNAL_TASK_SECRET nao configurado no ambiente.');
  }

  return receivedSecret && receivedSecret === expectedSecret;
}

function extractImageFromResponse(response) {
  const candidates = response?.candidates || [];
  let imagePart = null;
  let textPart = null;

  for (const candidate of candidates) {
    const parts = candidate?.content?.parts || [];

    for (const part of parts) {
      if (
        part?.inlineData?.data &&
        part?.inlineData?.mimeType &&
        part.inlineData.mimeType.startsWith('image/')
      ) {
        imagePart = part;
      }

      if (part?.text && !textPart) {
        textPart = part.text;
      }
    }
  }

  return {
    imagePart,
    textPart,
  };
}

function isImagenModel(model) {
  return typeof model === 'string' && model.startsWith('imagen-');
}

function normalizeImageOptions(rawOptions) {
  const options = rawOptions && typeof rawOptions === 'object' && !Array.isArray(rawOptions)
    ? rawOptions
    : {};
  const aspectRatio = typeof options.aspectRatio === 'string' && ALLOWED_ASPECT_RATIOS.has(options.aspectRatio)
    ? options.aspectRatio
    : '1:1';
  const imageSize = typeof options.imageSize === 'string' && ALLOWED_IMAGE_SIZES.has(options.imageSize.toUpperCase())
    ? options.imageSize.toUpperCase()
    : '1K';
  const outputMimeType = typeof options.outputMimeType === 'string' && ALLOWED_OUTPUT_MIME_TYPES.has(options.outputMimeType)
    ? options.outputMimeType
    : 'image/png';
  const compressionQuality = Number.isFinite(Number(options.compressionQuality))
    ? Math.max(0, Math.min(100, Number(options.compressionQuality)))
    : 75;

  return {
    aspectRatio,
    imageSize,
    outputMimeType,
    compressionQuality
  };
}

function buildGeminiConfig(imageOptions) {
  const config = {
    responseModalities: ['TEXT', 'IMAGE'],
    imageConfig: {
      aspectRatio: imageOptions.aspectRatio,
      imageSize: imageOptions.imageSize,
      imageOutputOptions: {
        mimeType: imageOptions.outputMimeType
      }
    }
  };

  if ('image/jpeg' === imageOptions.outputMimeType) {
    config.imageConfig.imageOutputOptions.compressionQuality = imageOptions.compressionQuality;
  }

  return config;
}

async function generateWithImagen({
  accessToken,
  finalPrompt,
  model,
  project,
  location,
  imageOptions
}) {
  if ('4K' === imageOptions.imageSize) {
    throw new RequestError(400, 'Modelos Imagen neste endpoint suportam no maximo 2K.');
  }

  const url = `https://${location}-aiplatform.googleapis.com/v1/projects/${project}/locations/${location}/publishers/google/models/${model}:predict`;
  const parameters = {
    sampleCount: 1,
    aspectRatio: imageOptions.aspectRatio,
    sampleImageSize: imageOptions.imageSize,
    outputOptions: {
      mimeType: imageOptions.outputMimeType
    }
  };

  if ('image/jpeg' === imageOptions.outputMimeType) {
    parameters.outputOptions.compressionQuality = imageOptions.compressionQuality;
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${accessToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      instances: [
        {
          prompt: finalPrompt
        }
      ],
      parameters
    })
  });

  const rawText = await response.text();
  let data = null;

  try {
    data = rawText ? JSON.parse(rawText) : null;
  } catch (error) {
    data = null;
  }

  if (!response.ok) {
    const errorMessage = data?.error?.message || rawText || 'Falha ao chamar o modelo Imagen.';
    throw new RequestError(response.status || 502, errorMessage);
  }

  const prediction = Array.isArray(data?.predictions) ? data.predictions[0] : null;

  if (!prediction?.bytesBase64Encoded || !prediction?.mimeType) {
    throw new RequestError(502, 'O modelo Imagen nao retornou uma imagem valida.');
  }

  return {
    imageBase64: prediction.bytesBase64Encoded,
    mimeType: prediction.mimeType,
    text: prediction.prompt || null
  };
}

function nowIso() {
  return new Date().toISOString();
}

function currentProjectId() {
  return process.env.GOOGLE_CLOUD_PROJECT || process.env.GCLOUD_PROJECT || process.env.GCP_PROJECT || '';
}

function asyncInfrastructureProjectId() {
  const configuredProject = typeof process.env.ASYNC_INFRA_PROJECT_ID === 'string'
    ? process.env.ASYNC_INFRA_PROJECT_ID.trim()
    : '';

  return configuredProject || currentProjectId();
}

function vertexProjectId() {
  const targetProjectId = typeof process.env.TARGET_PROJECT_ID === 'string'
    ? process.env.TARGET_PROJECT_ID.trim()
    : '';
  const currentProject = currentProjectId().trim();

  if (!targetProjectId) {
    throw new RequestError(
      500,
      'TARGET_PROJECT_ID nao configurado. Geracao bloqueada para evitar cobranca no projeto principal.'
    );
  }

  if (currentProject && targetProjectId === currentProject) {
    throw new RequestError(
      500,
      'TARGET_PROJECT_ID aponta para o projeto principal do hub. Geracao bloqueada para evitar cobranca no projeto principal.'
    );
  }

  return targetProjectId;
}

function vertexLocation() {
  return process.env.GOOGLE_CLOUD_LOCATION || 'global';
}

function mimeToExtension(mimeType) {
  const map = {
    'image/png': 'png',
    'image/jpeg': 'jpg',
    'image/webp': 'webp'
  };

  return map[mimeType] || 'bin';
}

function resultUrlTtlSeconds() {
  const rawValue = Number(process.env.RESULT_URL_TTL_SECONDS);

  if (!Number.isFinite(rawValue) || rawValue <= 0) {
    return DEFAULT_RESULT_URL_TTL_SECONDS;
  }

  return Math.max(60, Math.min(86400, Math.floor(rawValue)));
}

function ensureAsyncInfrastructureConfigured() {
  const missing = [];

  if (!asyncInfrastructureProjectId()) {
    missing.push('GOOGLE_CLOUD_PROJECT ou ASYNC_INFRA_PROJECT_ID');
  }

  if (!process.env.JOBS_BUCKET) {
    missing.push('JOBS_BUCKET');
  }

  if (!process.env.TASKS_QUEUE) {
    missing.push('TASKS_QUEUE');
  }

  if (!process.env.TASKS_LOCATION) {
    missing.push('TASKS_LOCATION');
  }

  if (!process.env.INTERNAL_TASK_SECRET) {
    missing.push('INTERNAL_TASK_SECRET');
  }

  if (missing.length > 0) {
    throw new RequestError(
      500,
      `Infraestrutura async incompleta. Configure: ${missing.join(', ')}.`
    );
  }
}

function jobCollection() {
  const projectId = asyncInfrastructureProjectId();

  if (!firestoreClients.has(projectId)) {
    firestoreClients.set(projectId, new Firestore({ projectId }));
  }

  return firestoreClients.get(projectId).collection(JOBS_COLLECTION);
}

function getServiceBaseUrl(req) {
  const protocolHeader = req.get('X-Forwarded-Proto');
  const proto = protocolHeader
    ? protocolHeader.split(',')[0].trim()
    : 'https';
  const host = req.get('X-Forwarded-Host') || req.get('Host');

  if (!host) {
    throw new RequestError(500, 'Nao foi possivel determinar a URL base do servico.');
  }

  return `${proto}://${host}`;
}

async function uploadBase64Object(objectName, base64Data, mimeType) {
  const buffer = Buffer.from(base64Data, 'base64');
  const bucket = storage.bucket(process.env.JOBS_BUCKET);
  const file = bucket.file(objectName);

  await file.save(buffer, {
    resumable: false,
    metadata: {
      contentType: mimeType,
      cacheControl: 'private, max-age=0, no-store'
    }
  });

  return {
    objectName,
    mimeType,
    size: buffer.length
  };
}

async function downloadObjectAsBase64(objectName) {
  const bucket = storage.bucket(process.env.JOBS_BUCKET);
  const [buffer] = await bucket.file(objectName).download();
  return buffer.toString('base64');
}

async function downloadObjectBuffer(objectName) {
  const bucket = storage.bucket(process.env.JOBS_BUCKET);
  const [buffer] = await bucket.file(objectName).download();
  return buffer;
}

async function createSignedResultUrl(objectName) {
  const bucket = storage.bucket(process.env.JOBS_BUCKET);
  const file = bucket.file(objectName);
  const expiresAt = Date.now() + (resultUrlTtlSeconds() * 1000);
  const [url] = await file.getSignedUrl({
    version: 'v4',
    action: 'read',
    expires: expiresAt
  });

  return {
    url,
    expiresAt: new Date(expiresAt).toISOString()
  };
}

async function enqueueJobTask(req, jobId) {
  const parent = tasksClient.queuePath(
    asyncInfrastructureProjectId(),
    process.env.TASKS_LOCATION,
    process.env.TASKS_QUEUE
  );
  const payload = Buffer.from(JSON.stringify({ jobId })).toString('base64');
  const task = {
    httpRequest: {
      httpMethod: 'POST',
      url: `${getServiceBaseUrl(req)}/internal/process-job`,
      headers: {
        'Content-Type': 'application/json',
        'X-Internal-Task-Secret': process.env.INTERNAL_TASK_SECRET,
      },
      body: payload,
    },
    dispatchDeadline: {
      seconds: 1800
    }
  };

  await tasksClient.createTask({
    parent,
    task
  });
}

function serializeJob(jobId, jobData) {
  const output = jobData?.output || null;
  const error = jobData?.error || null;

  return {
    ok: true,
    jobId,
    status: jobData?.status || 'unknown',
    message: jobData?.message || null,
    model: jobData?.model || null,
    mimeType: output?.mimeType || null,
    text: output?.text || null,
    hasResult: !!output?.objectName,
    error: error?.message || null,
    details: error?.details || null,
    createdAt: jobData?.createdAt || null,
    updatedAt: jobData?.updatedAt || null
  };
}

function parseBodyObject(body) {
  if (body == null) {
    return {};
  }

  if (Buffer.isBuffer(body)) {
    const text = body.toString('utf8').trim();
    try {
      return text ? JSON.parse(text) : {};
    } catch (error) {
      throw new RequestError(400, 'Corpo JSON invalido.');
    }
  }

  if (typeof body === 'string') {
    const text = body.trim();
    try {
      return text ? JSON.parse(text) : {};
    } catch (error) {
      throw new RequestError(400, 'Corpo JSON invalido.');
    }
  }

  if (typeof body === 'object') {
    return body;
  }

  throw new RequestError(400, 'Corpo da requisicao invalido.');
}

function normalizeReferenceImages(payload) {
  const normalizedImages = [];
  const rawReferenceImages = Array.isArray(payload.referenceImages)
    ? payload.referenceImages
    : [];

  for (const rawImage of rawReferenceImages) {
    if (!rawImage || typeof rawImage !== 'object' || Array.isArray(rawImage)) {
      throw new RequestError(400, 'Cada item de referenceImages deve ser um objeto valido.');
    }

    const imageBase64 = typeof rawImage.imageBase64 === 'string'
      ? rawImage.imageBase64.trim()
      : '';
    const mimeType = typeof rawImage.mimeType === 'string'
      ? rawImage.mimeType.trim()
      : '';

    if (!imageBase64) {
      throw new RequestError(400, 'Cada item de referenceImages precisa ter imageBase64.');
    }

    if (!mimeType || !mimeType.startsWith('image/')) {
      throw new RequestError(400, 'Cada item de referenceImages precisa ter um mimeType de imagem valido.');
    }

    normalizedImages.push({
      data: imageBase64,
      mimeType
    });
  }

  if (normalizedImages.length > 7) {
    throw new RequestError(400, 'Envie no maximo 7 imagens de referencia.');
  }

  if (normalizedImages.length > 0) {
    return normalizedImages;
  }

  const imageBase64 = typeof payload.imageBase64 === 'string'
    ? payload.imageBase64.trim()
    : '';
  const mimeType = typeof payload.mimeType === 'string'
    ? payload.mimeType.trim()
    : '';

  if (!imageBase64) {
    if (mimeType) {
      throw new RequestError(400, 'mimeType foi enviado sem imageBase64.');
    }

    return [];
  }

  if (!mimeType || !mimeType.startsWith('image/')) {
    throw new RequestError(400, 'Envie um mimeType de imagem valido, por exemplo image/png ou image/jpeg.');
  }

  return [{
    data: imageBase64,
    mimeType
  }];
}

function normalizeRequestPayload(body) {
  const payload = parseBodyObject(body);
  const {
    template,
    userPrompt,
    imageBase64,
    mimeType,
    model,
    imageOptions,
    referenceImages
  } = payload;

  if (template != null && typeof template !== 'string') {
    throw new RequestError(400, 'Envie um template valido quando ele for usado.');
  }

  if (userPrompt != null && typeof userPrompt !== 'string') {
    throw new RequestError(400, 'userPrompt deve ser uma string quando enviado.');
  }

  if (imageBase64 != null && typeof imageBase64 !== 'string') {
    throw new RequestError(400, 'imageBase64 deve ser uma string quando enviado.');
  }

  if (mimeType != null && typeof mimeType !== 'string') {
    throw new RequestError(400, 'Envie um mimeType de imagem valido, por exemplo image/png ou image/jpeg.');
  }

  if (model != null && typeof model !== 'string') {
    throw new RequestError(400, 'model deve ser uma string quando enviado.');
  }

  if (imageOptions != null && (typeof imageOptions !== 'object' || Array.isArray(imageOptions))) {
    throw new RequestError(400, 'imageOptions deve ser um objeto quando enviado.');
  }

  if (referenceImages != null && !Array.isArray(referenceImages)) {
    throw new RequestError(400, 'referenceImages deve ser uma lista quando enviado.');
  }

  const normalizedTemplate = typeof template === 'string'
    ? template.trim()
    : '';
  const normalizedUserPrompt = typeof userPrompt === 'string'
    ? userPrompt.trim()
    : '';
  const normalizedReferenceImages = normalizeReferenceImages({
    imageBase64,
    mimeType,
    referenceImages
  });
  const normalizedModel = typeof model === 'string' && model.trim()
    ? model.trim()
    : 'gemini-3-pro-image-preview';

  return {
    template: normalizedTemplate,
    userPrompt: normalizedUserPrompt,
    referenceImages: normalizedReferenceImages,
    model: normalizedModel,
    imageOptions: normalizeImageOptions(imageOptions),
    hasImage: normalizedReferenceImages.length > 0
  };
}

function validatePayloadShape(payload) {
  if (!payload.template && !payload.userPrompt) {
    throw new RequestError(400, 'Envie um template ou um userPrompt valido.');
  }
}

async function generateImageRequest(input) {
  validatePayloadShape(input);

  const finalPrompt = buildFinalPrompt(input.template, input.userPrompt, input.hasImage);
  const accessToken = await getImpersonatedAccessToken();
  const selectedModel = input.model || 'gemini-3-pro-image-preview';

  if (isImagenModel(selectedModel)) {
    if (input.hasImage) {
      throw new RequestError(400, 'Modelos Imagen neste endpoint nao aceitam imagem de referencia.');
    }

    const imagenResult = await generateWithImagen({
      accessToken,
      finalPrompt,
      model: selectedModel,
      project: vertexProjectId(),
      location: vertexLocation(),
      imageOptions: input.imageOptions
    });

    return {
      template: input.template || null,
      model: selectedModel,
      imageOptions: input.imageOptions,
      mimeType: imagenResult.mimeType,
      imageBase64: imagenResult.imageBase64,
      text: imagenResult.text || null
    };
  }

  const ai = new GoogleGenAI({
    vertexai: true,
    project: vertexProjectId(),
    location: vertexLocation(),
    httpOptions: {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    },
  });

  const response = await ai.models.generateContent({
    model: selectedModel,
    contents: [
      {
        role: 'user',
        parts: buildModelParts(finalPrompt, input.referenceImages)
      }
    ],
    config: buildGeminiConfig(input.imageOptions)
  });

  const { imagePart, textPart } = extractImageFromResponse(response);

  if (!imagePart) {
    throw new RequestError(
      502,
      'O modelo respondeu, mas nao retornou imagem.',
      normalizeModelDetail(textPart)
    );
  }

  return {
    template: input.template || null,
    model: selectedModel,
    imageOptions: input.imageOptions,
    mimeType: imagePart.inlineData.mimeType,
    imageBase64: imagePart.inlineData.data,
    text: textPart || null
  };
}

async function handleCreateJob(req, res) {
  if (!validateSharedSecret(req)) {
    return res.status(401).json({
      error: 'Unauthorized'
    });
  }

  ensureAsyncInfrastructureConfigured();

  const input = normalizeRequestPayload(req.body);
  validatePayloadShape(input);

  const jobId = crypto.randomUUID();
  const referenceImages = [];

  if (input.hasImage) {
    for (let index = 0; index < input.referenceImages.length; index += 1) {
      const referenceImage = input.referenceImages[index];
      const objectName = `inputs/${jobId}-${index + 1}.${mimeToExtension(referenceImage.mimeType)}`;

      await uploadBase64Object(objectName, referenceImage.data, referenceImage.mimeType);
      referenceImages.push({
        objectName,
        mimeType: referenceImage.mimeType
      });
    }
  }

  const timestamp = nowIso();
  const jobData = {
    status: 'queued',
    message: 'Job criado e aguardando processamento.',
    createdAt: timestamp,
    updatedAt: timestamp,
    model: input.model,
    request: {
      template: input.template || null,
      userPrompt: input.userPrompt || null,
      imageOptions: input.imageOptions,
      referenceImages
    },
    output: null,
    error: null
  };

  await jobCollection().doc(jobId).set(jobData);

  try {
    await enqueueJobTask(req, jobId);
  } catch (error) {
    await jobCollection().doc(jobId).set({
      status: 'failed',
      message: 'Nao foi possivel colocar o job na fila.',
      updatedAt: nowIso(),
      error: {
        message: error?.message || String(error),
        details: error?.details || null
      }
    }, { merge: true });
    throw error;
  }

  return res.status(202).json({
    ok: true,
    jobId,
    status: 'queued',
    message: 'Job criado e enviado para fila.'
  });
}

async function handleGetJobStatus(req, res, jobId) {
  if (!validateSharedSecret(req)) {
    return res.status(401).json({
      error: 'Unauthorized'
    });
  }

  const snapshot = await jobCollection().doc(jobId).get();

  if (!snapshot.exists) {
    return res.status(404).json({
      error: 'Job nao encontrado.'
    });
  }

  const jobData = snapshot.data() || {};
  const payload = serializeJob(jobId, jobData);

  if ('completed' === jobData.status && jobData?.output?.objectName) {
    try {
      const signedResult = await createSignedResultUrl(jobData.output.objectName);
      payload.resultUrl = signedResult.url;
      payload.resultUrlExpiresAt = signedResult.expiresAt;
      payload.temporaryResult = true;
    } catch (error) {
      console.warn('Falha ao gerar signed URL do resultado:', error);
      payload.resultUrl = null;
      payload.resultUrlExpiresAt = null;
      payload.temporaryResult = true;
    }
  }

  return res.status(200).json(payload);
}

async function handleGetJobResult(req, res, jobId) {
  if (!validateSharedSecret(req)) {
    return res.status(401).json({
      error: 'Unauthorized'
    });
  }

  ensureAsyncInfrastructureConfigured();

  const snapshot = await jobCollection().doc(jobId).get();

  if (!snapshot.exists) {
    return res.status(404).json({
      error: 'Job nao encontrado.'
    });
  }

  const jobData = snapshot.data() || {};

  if ('completed' !== jobData.status || !jobData?.output?.objectName) {
    return res.status(409).json({
      error: 'O job ainda nao possui um resultado final.'
    });
  }

  const buffer = await downloadObjectBuffer(jobData.output.objectName);

  res.set('Content-Type', jobData.output.mimeType || 'application/octet-stream');
  res.set('Cache-Control', 'private, max-age=0, no-store');
  return res.status(200).send(buffer);
}

async function handleProcessJob(req, res) {
  if (!validateInternalTaskSecret(req)) {
    return res.status(401).json({
      error: 'Unauthorized'
    });
  }

  ensureAsyncInfrastructureConfigured();

  let jobId = '';

  try {
    const body = parseBodyObject(req.body);
    jobId = typeof body.jobId === 'string'
      ? body.jobId.trim()
      : '';

    if (!jobId) {
      throw new RequestError(400, 'Envie um jobId valido.');
    }

    const jobRef = jobCollection().doc(jobId);
    const snapshot = await jobRef.get();

    if (!snapshot.exists) {
      throw new RequestError(404, 'Job nao encontrado.');
    }

    const jobData = snapshot.data() || {};

    if ('completed' === jobData.status) {
      return res.status(200).json({
        ok: true,
        jobId,
        status: 'completed'
      });
    }

    if ('processing' === jobData.status) {
      return res.status(200).json({
        ok: true,
        jobId,
        status: 'processing'
      });
    }

    await jobRef.set({
      status: 'processing',
      message: 'Gerando imagem.',
      updatedAt: nowIso(),
      startedAt: nowIso(),
      error: null
    }, { merge: true });

    const storedReferenceImages = Array.isArray(jobData?.request?.referenceImages)
      ? jobData.request.referenceImages
      : [];
    const referenceImages = [];

    for (const referenceImage of storedReferenceImages) {
      if (!referenceImage?.objectName || !referenceImage?.mimeType) {
        continue;
      }

      referenceImages.push({
        data: await downloadObjectAsBase64(referenceImage.objectName),
        mimeType: referenceImage.mimeType
      });
    }

    const generationResult = await generateImageRequest({
      template: jobData?.request?.template || '',
      userPrompt: jobData?.request?.userPrompt || '',
      referenceImages,
      model: jobData?.model || 'gemini-3-pro-image-preview',
      imageOptions: jobData?.request?.imageOptions || normalizeImageOptions({}),
      hasImage: referenceImages.length > 0
    });
    const outputObjectName = `outputs/${jobId}.${mimeToExtension(generationResult.mimeType)}`;

    await uploadBase64Object(outputObjectName, generationResult.imageBase64, generationResult.mimeType);

    await jobRef.set({
      status: 'completed',
      message: 'Imagem pronta.',
      updatedAt: nowIso(),
      completedAt: nowIso(),
      output: {
        objectName: outputObjectName,
        mimeType: generationResult.mimeType,
        text: generationResult.text || null,
        imageOptions: generationResult.imageOptions
      },
      error: null
    }, { merge: true });

    return res.status(200).json({
      ok: true,
      jobId,
      status: 'completed'
    });
  } catch (error) {
    console.error('Erro ao processar job:', error);

    if (jobId) {
      try {
        await jobCollection().doc(jobId).set({
          status: 'failed',
          message: 'Falha ao gerar a imagem.',
          updatedAt: nowIso(),
          error: {
            message: error?.message || String(error),
            details: error?.details || null
          }
        }, { merge: true });
      } catch (updateError) {
        console.error('Erro ao atualizar job com falha:', updateError);
      }
    }

    return res.status(200).json({
      ok: false,
      jobId: jobId || null,
      status: 'failed',
      error: error?.message || String(error)
    });
  }
}

async function handleSyncGenerate(req, res) {
  if (!validateSharedSecret(req)) {
    return res.status(401).json({
      error: 'Unauthorized'
    });
  }

  const input = normalizeRequestPayload(req.body);
  const result = await generateImageRequest(input);

  return res.status(200).json({
    ok: true,
    template: result.template,
    projectUsed: vertexProjectId(),
    serviceAccountUsed: process.env.TARGET_SERVICE_ACCOUNT,
    model: result.model,
    imageOptions: result.imageOptions,
    mimeType: result.mimeType,
    imageBase64: result.imageBase64,
    text: result.text || null
  });
}

function getRequestPath(req) {
  const rawPath = typeof req.path === 'string' && req.path
    ? req.path
    : (typeof req.url === 'string' ? req.url : '/');

  try {
    const parsed = new URL(rawPath, 'http://localhost');
    const pathname = parsed.pathname || '/';
    return pathname.length > 1 && pathname.endsWith('/')
      ? pathname.slice(0, -1)
      : pathname;
  } catch (error) {
    return '/';
  }
}

functions.http('helloHttp', async (req, res) => {
  res.set('Access-Control-Allow-Origin', '*');
  res.set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.set('Access-Control-Allow-Headers', 'Content-Type, X-Site-Secret, X-Internal-Task-Secret');
  res.set('Cache-Control', 'no-store');

  if (req.method === 'OPTIONS') {
    return res.status(204).send('');
  }

  const path = getRequestPath(req);
  const jobResultMatch = path.match(/^\/jobs\/([^/]+)\/result$/);
  const jobMatch = path.match(/^\/jobs\/([^/]+)$/);

  try {
    if ('POST' === req.method && '/jobs' === path) {
      return await handleCreateJob(req, res);
    }

    if ('GET' === req.method && jobResultMatch) {
      return await handleGetJobResult(req, res, decodeURIComponent(jobResultMatch[1]));
    }

    if ('GET' === req.method && jobMatch) {
      return await handleGetJobStatus(req, res, decodeURIComponent(jobMatch[1]));
    }

    if ('POST' === req.method && '/internal/process-job' === path) {
      return await handleProcessJob(req, res);
    }

    if ('POST' === req.method && '/' === path) {
      return await handleSyncGenerate(req, res);
    }

    return res.status(404).json({
      error: 'Rota nao encontrada.'
    });
  } catch (error) {
    const status = error instanceof RequestError && error.status
      ? error.status
      : 500;
    const message = error?.message || String(error);
    const details = error instanceof RequestError && error.details
      ? error.details
      : '';

    console.error('Erro no hub:', error);

    return res.status(status).json({
      error: status >= 500 ? 'Falha ao processar requisicao.' : message,
      details: status >= 500 ? (details || message) : (details || undefined)
    });
  }
});
