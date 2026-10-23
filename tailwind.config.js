/** Цвета берутся из CSS-переменных (src/index.css), поэтому тёмная тема — это просто другой набор переменных. */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{ts,tsx}"],
  theme: {
    extend: {
      colors: {
        canvas: token("canvas"),
        surface: token("surface"),
        subtle: token("subtle"),
        ink: token("ink"),
        muted: token("muted"),
        line: token("line"),
        "line-strong": token("line-strong"),
        brand: {
          DEFAULT: token("brand"),
          hover: token("brand-hover"),
          soft: token("brand-soft"),
          on: token("on-brand"),
        },
        danger: token("danger"),
        warning: token("warning"),
        panel: {
          DEFAULT: token("panel"),
          ink: token("panel-ink"),
          muted: token("panel-muted"),
          line: token("panel-line"),
          danger: token("panel-danger"),
        },
        highlight: {
          DEFAULT: token("highlight"),
          on: token("on-highlight"),
        },
        chart: {
          principal: token("chart-principal"),
          interest: token("chart-interest"),
          good: token("chart-good"),
          warn: token("chart-warn"),
        },
      },
      fontFamily: {
        sans: ['"Onest Variable"', "system-ui", "-apple-system", "Segoe UI", "Roboto", "sans-serif"],
      },
      borderRadius: {
        "4xl": "2rem",
      },
      boxShadow: {
        card: "0 1px 2px rgb(16 24 20 / 0.04), 0 8px 24px -12px rgb(16 24 20 / 0.12)",
      },
    },
  },
  plugins: [],
};
