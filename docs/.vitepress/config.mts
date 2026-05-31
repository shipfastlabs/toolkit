import { defineConfig } from 'vitepress'
import llmstxt from 'vitepress-plugin-llms'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Toolkit by Ship Fast Labs',
  description: 'A community catalog of reusable AI tools for the Laravel AI SDK.',
  cleanUrls: true,
  lastUpdated: true,

  // Generate llms.txt, llms-full.txt and per-page markdown for LLM consumption.
  vite: {
    plugins: [
      llmstxt({
        title: 'shipfastlabs/toolkit',
        description: 'A community catalog of reusable AI tools for the Laravel AI SDK.',
        ignoreFiles: ['README.md'],
      }),
    ],
  },

  // Dark-only design (hides the light/dark toggle).
  appearance: 'force-dark',

  head: [
    ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' }],
    ['link', {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap',
    }],

    // Open Graph / Twitter card (image lives at docs/public/og.png).
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Toolkit by Ship Fast Labs' }],
    ['meta', { property: 'og:description', content: 'Reusable AI tools for the Laravel AI SDK.' }],
    ['meta', { property: 'og:url', content: 'https://toolkit.shipfastlabs.com/' }],
    ['meta', { property: 'og:image', content: 'https://toolkit.shipfastlabs.com/og.png' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:image', content: 'https://toolkit.shipfastlabs.com/og.png' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'Tools', link: '/tools/calculator' },
    ],

    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Contributing', link: '/guide/contributing' },
        ],
      },
      {
        text: 'Tools',
        items: [
          { text: 'Calculator', link: '/tools/calculator' },
          { text: 'Database', link: '/tools/database' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/shipfastlabs/toolkit' },
    ],

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 Shipfastlabs',
    },
  },
})
