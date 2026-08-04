import typography from '@tailwindcss/typography'

export default {
  content: [
    './theme/**/*.php',
    './theme/**/*.twig',
    './theme/**/*.js',
    './theme/**/*.json',
  ],
  plugins: [typography],
}
