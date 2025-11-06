function openInLargeWindow(path) {
  if (typeof path !== 'string' || !path.trim()) {
    console.error('Invalid path provided.');
    return;
  }

  const width = Math.floor(window.screen.availWidth * 0.9);
  const height = Math.floor(window.screen.availHeight * 0.9);
  const left = Math.floor((window.screen.availWidth - width) / 2);
  const top = Math.floor((window.screen.availHeight - height) / 2);

  const newWindow = window.open(
    'about:blank',
    '_blank',
    `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
  );

  if (!newWindow) {
    console.error('Failed to open new window. Pop-up blocker may be enabled.');
    return;
  }
  newWindow.location.href = path;
}
