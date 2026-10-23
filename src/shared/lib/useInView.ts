import { useEffect, useState, type RefObject } from "react";

/** Виден ли элемент в окне. Без IntersectionObserver (тесты, старые браузеры) считаем, что виден. */
export function useInView(ref: RefObject<Element>, rootMargin = "0px"): boolean {
  const [inView, setInView] = useState(true);

  useEffect(() => {
    const el = ref.current;
    if (!el || typeof IntersectionObserver === "undefined") return;
    const observer = new IntersectionObserver(([entry]) => setInView(entry.isIntersecting), { rootMargin });
    observer.observe(el);
    return () => observer.disconnect();
  }, [ref, rootMargin]);

  return inView;
}
