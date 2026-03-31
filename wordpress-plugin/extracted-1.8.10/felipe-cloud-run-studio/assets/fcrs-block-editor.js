(function (blocks, element, components, blockEditor) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var InspectorControls = blockEditor.InspectorControls;
  var useBlockProps = blockEditor.useBlockProps;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var Placeholder = components.Placeholder;
  var Notice = components.Notice;
  var BaseControl = components.BaseControl;

  var templateOptions = [{ value: '', label: 'Selecione um template' }].concat(
    Array.isArray(window.FCRSBlockData && window.FCRSBlockData.templates)
      ? window.FCRSBlockData.templates
      : []
  );
  var modelOptions = Array.isArray(window.FCRSBlockData && window.FCRSBlockData.models)
    ? window.FCRSBlockData.models
    : [{ value: '', label: 'Usar modelo padrao do template' }];

  function getTemplateLabel(templateKey) {
    var found = templateOptions.find(function (option) {
      return option.value === templateKey;
    });

    return found ? found.label : 'Nenhum template selecionado';
  }

  function getModelLabel(modelKey) {
    if (!modelKey) {
      return 'Padrao do template';
    }

    var found = modelOptions.find(function (option) {
      return option.value === modelKey;
    });

    return found ? found.label : modelKey;
  }

  blocks.registerBlockType('felipe-ai-studio/generator', {
    apiVersion: 2,
    title: 'Menezes Studio',
    description: 'Insere um gerador de imagem com template escolhido direto no bloco.',
    icon: 'format-image',
    category: 'widgets',
    attributes: {
      templateKey: {
        type: 'string',
        default: ''
      },
      model: {
        type: 'string',
        default: ''
      },
      title: {
        type: 'string',
        default: 'Gerador de imagem'
      },
      submitLabel: {
        type: 'string',
        default: 'Gerar imagem'
      }
    },
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var templateMissing = !attributes.templateKey;
      var blockProps = useBlockProps({
        className: 'fcrs-editor-block'
      });
      var inlineControls = el(
        'div',
        {
          style: {
            width: '100%',
            maxWidth: '720px',
            display: 'grid',
            gap: '12px'
          }
        },
        el(SelectControl, {
          label: 'Template',
          value: attributes.templateKey,
          options: templateOptions,
          onChange: function (value) {
            setAttributes({ templateKey: value });
          }
        }),
        el(SelectControl, {
          label: 'Modelo',
          value: attributes.model,
          options: modelOptions,
          onChange: function (value) {
            setAttributes({ model: value });
          }
        }),
        el(TextControl, {
          label: 'Titulo',
          value: attributes.title,
          onChange: function (value) {
            setAttributes({ title: value });
          }
        })
      );

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'Configuracao do gerador', initialOpen: true },
            el(SelectControl, {
              label: 'Template',
              value: attributes.templateKey,
              options: templateOptions,
              onChange: function (value) {
                setAttributes({ templateKey: value });
              }
            }),
            el(SelectControl, {
              label: 'Modelo',
              value: attributes.model,
              options: modelOptions,
              onChange: function (value) {
                setAttributes({ model: value });
              }
            }),
            el(TextControl, {
              label: 'Titulo',
              value: attributes.title,
              onChange: function (value) {
                setAttributes({ title: value });
              }
            })
          )
        ),
        el(
          'div',
          blockProps,
          el(
            Placeholder,
            {
              label: 'Menezes Studio',
              instructions: 'Edite este bloco por aqui mesmo ou pela lateral. O visitante podera subir imagens e clicar para gerar.'
            },
            inlineControls,
            templateMissing
              ? el(Notice, { status: 'warning', isDismissible: false }, 'Selecione um template para ativar o gerador neste bloco.')
              : el(
                  BaseControl,
                  { label: 'Resumo do bloco' },
                  el(
                    'div',
                    { style: { width: '100%' } },
                    el('p', null, 'Template: ', el('strong', null, getTemplateLabel(attributes.templateKey))),
                    el('p', null, 'Modelo: ', el('strong', null, getModelLabel(attributes.model))),
                    el('p', null, 'Titulo: ', el('strong', null, attributes.title || 'Gerador de imagem')),
                    el('p', null, 'Botao: ', el('strong', null, 'Padrao traduzido pelo idioma do template')),
                    el('p', { style: { marginTop: '12px', color: '#50575e' } }, 'No front, esse bloco exibira upload, botao de gerar e resultado.')
                  )
                )
          )
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.blockEditor
);
