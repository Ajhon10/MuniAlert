let locks = 0;
let priorOverflow = '';

// Nested confirmations must not unlock a parent dialog or leave the page locked.
export function lockModalScroll() {
  if (locks++ === 0) {
    priorOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
  }
  let released = false;
  return () => {
    if (released) return;
    released = true;
    if (--locks === 0) document.body.style.overflow = priorOverflow;
  };
}
