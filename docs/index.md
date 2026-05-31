---
layout: home

hero:
  name: AI SDK Toolkit
  text: Reusable AI tools for Laravel.
  tagline: A community catalog of small, tested, independently-installable tools for the Laravel AI SDK.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/shipfastlabs/toolkit

features:
  - title: Install only what you need
    details: Each tool is its own tiny package depending on nothing but laravel/ai. No shared core, no bloat. Require just the ones your agent uses.
  - title: Safe by default
    details: Read-only query enforcement, host allow-lists, no eval, capped output. Guardrails are baked in so the model can act without doing damage.
  - title: Drop-in for the Laravel AI SDK
    details: Every tool implements Laravel\Ai\Contracts\Tool. Instantiate it, hand it to an agent, done. No configuration or wiring required.
---
