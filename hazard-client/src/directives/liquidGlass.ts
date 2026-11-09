import type { ObjectDirective } from "vue";

/**
 * Opt-in material enhancement; use the URL only in backdrop-filter, never filter.
 * CSS owns the translucent fill, rim, and the blur fallback. This directive adds
 * an edge-only lens and --glass-pointer-x/y for its decorative highlight.
 */
const svgNamespace = "http://www.w3.org/2000/svg";
const cleanups = new WeakMap<HTMLElement, () => void>();
let nextFilterId = 0;

function svgElement<K extends keyof SVGElementTagNameMap>(
  tag: K,
  attributes: Record<string, string>,
): SVGElementTagNameMap[K] {
  const element = document.createElementNS(svgNamespace, tag);
  for (const [name, value] of Object.entries(attributes))
    element.setAttribute(name, value);
  return element;
}

function displacementMap(width: number, height: number, radius: number) {
  // A narrow header needs only a small map; larger surfaces have a pixel budget.
  const resolution = Math.min(
    1,
    768 / Math.max(width, height),
    Math.sqrt(131072 / (width * height)),
  );
  const canvas = document.createElement("canvas");
  canvas.width = Math.max(1, Math.round(width * resolution));
  canvas.height = Math.max(1, Math.round(height * resolution));
  const context = canvas.getContext("2d");
  if (!context) return null;
  const pixels = context.createImageData(canvas.width, canvas.height);
  const edge = Math.min(12, height / 3);

  for (let y = 0; y < canvas.height; y++) {
    for (let x = 0; x < canvas.width; x++) {
      const px = ((x + 0.5) / canvas.width - 0.5) * width;
      const py = ((y + 0.5) / canvas.height - 0.5) * height;
      const qx = Math.abs(px) - (width / 2 - radius);
      const qy = Math.abs(py) - (height / 2 - radius);
      const ox = Math.max(qx, 0);
      const oy = Math.max(qy, 0);
      const length = Math.hypot(ox, oy);
      const distance = radius - length - Math.min(Math.max(qx, qy), 0);
      let nx = 0;
      let ny = 0;
      let strength = 0;
      if (distance > 0 && distance < edge) {
        // Smoothly return to neutral at both the outer rim and clear center.
        strength = Math.sin((distance / edge) * Math.PI);
        if (length > 0) {
          nx = (ox / length) * Math.sign(px);
          ny = (oy / length) * Math.sign(py);
        } else if (qx > qy) nx = Math.sign(px);
        else ny = Math.sign(py);
      }
      const offset = (y * canvas.width + x) * 4;
      pixels.data[offset] = Math.round(127.5 + nx * strength * 127.5);
      pixels.data[offset + 1] = Math.round(127.5 + ny * strength * 127.5);
      pixels.data[offset + 2] = 128;
      pixels.data[offset + 3] = 255;
    }
  }
  context.putImageData(pixels, 0, 0);
  return canvas.toDataURL("image/png");
}

export const vLiquidGlass: ObjectDirective<HTMLElement> = {
  mounted(element) {
    element.setAttribute("data-liquid-glass", "");
    const reducedMotion = matchMedia("(prefers-reduced-motion: reduce)");
    const reducedTransparency = matchMedia(
      "(prefers-reduced-transparency: reduce)",
    );
    const moreContrast = matchMedia("(prefers-contrast: more)");
    const forcedColors = matchMedia("(forced-colors: active)");
    const finePointer = matchMedia("(hover: hover) and (pointer: fine)");
    const preferences = [
      reducedMotion,
      reducedTransparency,
      moreContrast,
      forcedColors,
      finePointer,
    ];
    // URL syntax support alone does not guarantee SVG backdrop rendering.
    // Restrict this enhancement to desktop/Android Chromium; iOS uses WebKit.
    const supportsRefraction =
      /(?:Chrome|Chromium)\//.test(navigator.userAgent) &&
      !/(?:iPhone|iPad|iPod)/.test(navigator.userAgent) &&
      CSS.supports("backdrop-filter", 'url("#liquid-glass-probe")');
    let svg: SVGSVGElement | undefined;
    let geometry = "";
    let layoutFrame = 0;
    let pointerFrame = 0;
    let pointerX = 50;
    let pointerY = 50;
    let disposed = false;

    const effectsAllowed = () =>
      !reducedMotion.matches &&
      !reducedTransparency.matches &&
      !moreContrast.matches &&
      !forcedColors.matches;

    function clearLens() {
      element.style.removeProperty("--glass-refraction");
      svg?.remove();
      svg = undefined;
      geometry = "";
    }

    function renderLens() {
      layoutFrame = 0;
      if (disposed) return;
      if (!supportsRefraction || !effectsAllowed()) {
        clearLens();
        return;
      }
      const width = element.offsetWidth;
      const height = element.offsetHeight;
      if (!width || !height) {
        clearLens();
        return;
      }
      const rawRadius = getComputedStyle(element).borderTopLeftRadius;
      const radius = Math.min(
        width / 2,
        height / 2,
        rawRadius.includes("%")
          ? (parseFloat(rawRadius) / 100) * Math.min(width, height)
          : parseFloat(rawRadius) || 0,
      );
      const nextGeometry = `${width}:${height}:${radius}`;
      if (nextGeometry === geometry) return;
      const map = displacementMap(width, height, radius);
      if (!map) return;

      clearLens();
      const id = `bantay-glass-lens-${++nextFilterId}`;
      svg = svgElement("svg", {
        width: "0",
        height: "0",
        "aria-hidden": "true",
        focusable: "false",
        "data-glass-filter": "",
      });
      svg.style.cssText = "position:fixed;pointer-events:none;overflow:hidden";
      const filter = svgElement("filter", {
        id,
        x: "0",
        y: "0",
        width: String(width),
        height: String(height),
        filterUnits: "userSpaceOnUse",
        primitiveUnits: "userSpaceOnUse",
        "color-interpolation-filters": "sRGB",
      });
      filter.append(
        svgElement("feGaussianBlur", {
          in: "SourceGraphic",
          stdDeviation: "0.6",
          result: "soft-backdrop",
        }),
        svgElement("feImage", {
          href: map,
          x: "0",
          y: "0",
          width: String(width),
          height: String(height),
          preserveAspectRatio: "none",
          result: "edge-map",
        }),
        svgElement("feDisplacementMap", {
          in: "soft-backdrop",
          in2: "edge-map",
          scale: "18",
          xChannelSelector: "R",
          yChannelSelector: "G",
        }),
      );
      svg.append(filter);
      document.body.append(svg);
      geometry = nextGeometry;
      element.style.setProperty("--glass-refraction", `url("#${id}")`);
    }

    function scheduleLens() {
      if (!layoutFrame) layoutFrame = requestAnimationFrame(renderLens);
    }

    function resetPointer() {
      cancelAnimationFrame(pointerFrame);
      pointerFrame = 0;
      element.style.removeProperty("--glass-pointer-x");
      element.style.removeProperty("--glass-pointer-y");
    }

    function movePointer(event: PointerEvent) {
      if (
        !effectsAllowed() ||
        !finePointer.matches ||
        event.pointerType !== "mouse"
      )
        return;
      pointerX = event.clientX;
      pointerY = event.clientY;
      if (pointerFrame) return;
      pointerFrame = requestAnimationFrame(() => {
        pointerFrame = 0;
        const rect = element.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        const x = Math.max(
          0,
          Math.min(100, ((pointerX - rect.left) / rect.width) * 100),
        );
        const y = Math.max(
          0,
          Math.min(100, ((pointerY - rect.top) / rect.height) * 100),
        );
        element.style.setProperty("--glass-pointer-x", `${x.toFixed(1)}%`);
        element.style.setProperty("--glass-pointer-y", `${y.toFixed(1)}%`);
      });
    }

    function preferenceChanged() {
      resetPointer();
      scheduleLens();
    }

    const observer = new ResizeObserver(scheduleLens);
    observer.observe(element);
    element.addEventListener("pointermove", movePointer, { passive: true });
    element.addEventListener("pointerleave", resetPointer, { passive: true });
    for (const preference of preferences)
      preference.addEventListener("change", preferenceChanged);
    scheduleLens();
    cleanups.set(element, () => {
      disposed = true;
      cancelAnimationFrame(layoutFrame);
      resetPointer();
      clearLens();
      observer.disconnect();
      element.removeEventListener("pointermove", movePointer);
      element.removeEventListener("pointerleave", resetPointer);
      for (const preference of preferences)
        preference.removeEventListener("change", preferenceChanged);
      element.removeAttribute("data-liquid-glass");
    });
  },
  unmounted(element) {
    cleanups.get(element)?.();
    cleanups.delete(element);
  },
};
