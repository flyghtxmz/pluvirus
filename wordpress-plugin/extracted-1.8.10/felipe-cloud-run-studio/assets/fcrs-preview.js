(function () {
  function setupPreview(root) {
    var input = root.querySelector('input[name="referenceImage"]');
    var wrapper = root.querySelector('[data-fcrs-preview]');
    var image = root.querySelector('[data-fcrs-preview-image]');

    if (!input || !wrapper || !image) {
      return;
    }

    input.addEventListener('change', function () {
      var file = input.files && input.files[0] ? input.files[0] : null;

      if (!file || !file.type || file.type.indexOf('image/') !== 0) {
        image.removeAttribute('src');
        wrapper.style.display = 'none';
        return;
      }

      var reader = new FileReader();

      reader.onload = function (event) {
        image.src = event.target && event.target.result ? event.target.result : '';
        wrapper.style.display = image.src ? 'block' : 'none';
      };

      reader.readAsDataURL(file);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var panels = document.querySelectorAll('.fcrs-panel');

    panels.forEach(function (panel) {
      setupPreview(panel);
    });
  });
})();
