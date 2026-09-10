/* Presentation enhancement; submission and autosave remain on the existing page. */
(() => {
  'use strict';
  const editor = document.getElementById('work_desc');
  const counter = document.getElementById('wr-word-count');
  if (!editor || !counter) return;
  const updateCount = () => {
    const text = editor.value.trim();
    const words = text ? text.split(/\s+/u).length : 0;
    counter.textContent = `${words} ${words === 1 ? 'word' : 'words'} · At least 10 characters`;
  };
  editor.addEventListener('input', updateCount);
  updateCount();
})();
