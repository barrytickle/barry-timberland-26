import typography from "@tailwindcss/typography";

/** Percentage scale — use as w-pct-50, max-h-pct-75, min-w-pct-25, etc. */
const pct = Object.fromEntries(
  Array.from({ length: 21 }, (_, i) => {
    const value = i * 5;
    return [`pct-${value}`, `${value}%`];
  }),
);

// Define your safelisted class names here
const safelist = [
  "bg-red-500",
  "text-xl",
  "p-4",
  "w-[fit-content]",
  // Add more class names as needed
];

export default {
  content: [
    "./theme/views/**/*.twig",
    "./theme/blocks/**/*.twig",
    "./theme/components/**/*.twig",
    "./theme/assets/styles/**/*.scss",
    ...safelist.map((cls) => `dummy/${cls}.html`),
  ],
  theme: {
    extend: {
      fontFamily: {
        tight: ['"Inter Tight"', "ui-sans-serif", "system-ui", "sans-serif"],
      },

      colors: {
        dark: {
          950: "#0e1012",
          900: "#19181F",
          800: "#1E1B24",
          700: "#23202A",
          600: "#333139",
          500: "#4f4d55",
          400: "#C2C6DD",
          300: "#C2C6DD",
          200: "#d3d2d4",
          100: "#e9e9ea",
        },
        // Monochrome "ink" ramp + muted surface tint, from the Showcasy UI kit.
        muted: "#ebebeb",
        ink: {
          DEFAULT: "#030712",
          950: "#030712",
          900: "#111827",
          800: "#1f2937",
          700: "#374151",
          600: "#4b5563",
          500: "#6b7280",
          400: "#aeb2ba",
          300: "#d1d5db",
          200: "#e5e7eb",
          100: "#f9fafb",
          0: "#ffffff",
        },
      },

      width: {
        18: "4.5rem",
        ...pct,
      },

      height: {
        18: "4.5rem",
        ...pct,
      },

      minWidth: {
        ...pct,
      },

      maxWidth: {
        container: "1056px",
        ...pct,
      },

      minHeight: {
        ...pct,
      },

      maxHeight: {
        ...pct,
      },

      inset: {
        "-25": "-6.25rem",
      },

      padding: {
        18: "4.5rem",
        "11/12": "91.666667%",
        "3/2": "150%",
      },

      transitionDuration: {
        250: "250ms",
      },

      letterSpacing: { tightest: "-0.02em" },
      borderRadius: { card: "24px" },

      // Semantic type scale from the Showcasy UI kit — also exposed as
      // .h1-.h6 / .body-* / .display in theme.scss.
      fontSize: {
        display: ["2.25rem", { lineHeight: "1", fontWeight: "500" }],
        h1: ["clamp(3.25rem,7vw,6.5rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "700" }],
        h2: ["clamp(2.5rem,5.5vw,4.5rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "600" }],
        h3: ["clamp(2.25rem,5vw,4rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "600" }],
        h4: ["clamp(2rem,4.5vw,3.5rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "600" }],
        h5: ["clamp(1.75rem,3.5vw,2.5rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "600" }],
        h6: ["clamp(1.5rem,2.5vw,2rem)", { lineHeight: "1", letterSpacing: "-0.02em", fontWeight: "600" }],
        "body-28": ["1.75rem", { lineHeight: "1.2", letterSpacing: "0.03em" }],
        "body-22": ["1.375rem", { lineHeight: "1.3", letterSpacing: "0.02em" }],
        "body-18": ["1.125rem", { lineHeight: "1.5", letterSpacing: "0.02em" }],
        "body-16": ["1rem", { lineHeight: "1.5", letterSpacing: "0.02em" }],
        "body-14": ["0.875rem", { lineHeight: "1.5", letterSpacing: "0.01em" }],
        "body-12": ["0.75rem", { lineHeight: "1.5", letterSpacing: "0.01em" }],
      },

      keyframes: {
        marquee: { from: { transform: "translateX(0)" }, to: { transform: "translateX(-50%)" } },
      },
      animation: { marquee: "marquee 28s linear infinite" },
    },
  },
  plugins: [typography],
};
