export function toggleCode(e) {
  const card = e.target.closest(".card");
  const codeWrapper = card?.querySelector(".code-wrapper");
  if (!codeWrapper) return;
  e.target.checked
    ? codeWrapper.classList.remove("hidden")
    : codeWrapper.classList.add("hidden");
}

export function getBrowserScrollbarWidth() {
  return window.innerWidth - document.documentElement.clientWidth;
}
