(function(){
  const dz = document.getElementById('dropzone');
  const input = document.getElementById('fileInput');
  const preview = document.getElementById('preview');
  if (!dz || !input || !preview) return;

  function renderPreview(files){
    preview.innerHTML = '';
    if (!files || !files.length){ preview.classList.add('hidden'); return; }
    preview.classList.remove('hidden');
    Array.from(files).forEach(f => {
      const url = URL.createObjectURL(f);
      const wrap = document.createElement('div');
      wrap.className = 'item';
      const img = document.createElement('img');
      img.src = url;
      const label = document.createElement('div');
      label.className = 'name';
      label.textContent = f.name;
      wrap.appendChild(img);
      wrap.appendChild(label);
      preview.appendChild(wrap);
    });
  }

  dz.addEventListener('dragover', e => {
    e.preventDefault();
  });
  dz.addEventListener('drop', e => {
    e.preventDefault();
    if (e.dataTransfer && e.dataTransfer.files) {
      input.files = e.dataTransfer.files;
      renderPreview(input.files);
    }
  });
  input.addEventListener('change', () => renderPreview(input.files));
})();
