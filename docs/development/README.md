# Development Workflow Documentation

*A comprehensive guide to development workflows and tools in the Wave SaaS Framework*

---

## Overview

This documentation covers the development workflows, testing tools, and quality assurance practices for the Wave SaaS framework. Each aspect is documented in detail in its own file within this directory.

## Available Tools & Workflows

| Tool/Workflow | Description | Documentation |
|---------------|-------------|---------------|
| Composer Dev Script | Run server, queue, logs, and Vite concurrently | [Development Workflow](./workflow.md) |
| Queue System | Background job processing for intensive tasks | [Queue System](./queues.md) |
| Pest PHP | Modern testing framework with expressive syntax | [Pest Testing](./pest.md) |
| Laravel Dusk | Browser testing capabilities | [Dusk Testing](./dusk.md) |
| DuskAPIConf | API configuration for Dusk testing | [API Testing](./api_testing.md) |

## Implementation Notes

Wave emphasizes a test-driven development approach with comprehensive testing coverage for all new features and bug fixes.

---

*For more comprehensive documentation on all Wave features, visit the [official Wave documentation](https://devdojo.com/wave/docs).*
