---
layout: home

hero:
  name: AI SDK Toolkit
  text: Reusable AI tools for Laravel.
  tagline: A community catalog of small, tested, independently installable tools for the Laravel AI SDK.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/shipfastlabs/toolkit

features:
  - title: Install Only What You Need
    details: Each tool is its own package and requires only laravel/ai. There is no shared core, so you require just the tools your agents use.
  - title: Safe by Default
    details: Each tool ships guardrails such as read-only query enforcement, host allow-lists, and output limits, and never uses eval, so the model can act without causing damage.
  - title: Built for the Laravel AI SDK
    details: Every tool implements Laravel\Ai\Contracts\Tool. You instantiate it and pass it to an agent. No configuration or service providers are required.
---
