***
## <a name="benchmarking"></a>BENCHMARKING & PERFORMANCE GUIDELINES

This document serves as the technical foundation for auditing and optimizing
Slick. Drawing from over a decade of resolving fundamental technical barriers,
these guidelines are streamlined for clarity and accountability.

We encourage a healthy, skeptical review to ensure proper expectations and
completeness. However, re-engagement on this performance topic requires a deep
alignment with the technical challenges outlined below. This standard ensures
a fair, "apples-to-apples" analysis and fosters a truly productive, data-driven
exchange to maximize benefits for the wider community.

We recognize that the web is a moving target. This challenge is not a closed
door, but an open invitation to evolve. If your benchmarks reveal a bottleneck
in modern metrics like LCP or overall architecture, we don't just want to hear
about it—we want to collaborate on the fix. Data is the bridge between a
complaint and a contribution. However, to maintain compliance with
**Core Web Vitals**, minor performance oversights should be viewed as
opportunities for fine-tuning, not as symptoms of architectural failure. We do
not retreat into basic workarounds that compromise the visual experience.

### Prove it yourself!
We recognize that seasoned experts may find this baseline familiar. Please
regard this as a technical foundation; if you are already proficient with the
basics, feel free to skip ahead to the primary contribution requirements.

#### Standardized Benchmarking Protocols

To ensure a well-informed and constructive judgment, please follow these
protocols:

- **Understand the Ecosystem:**

  Familiarize yourself with **Core Web Vitals** (CWV) and the specific
  configurations within Blazy/Slick UIs, including Media formatter forms. Many
  perceived "flaws" are often the result of misconfiguration rather than
  code limitations. We do have oversights, and they are normally taken care of
  by bug fixes. However specific to this audit, elaborate benchmarking is
  required for fairness.

- **Comparative Analysis:**

  - **Scope:**

    Provide a direct comparison between Slick and alternative
    modules, or the Slick API vs. custom theme-based implementations.

  - **Baseline:**

    Use code released prior to **2025-10-20** to establish a fair historical
    baseline.

  - **Accountability:**

    Testing the latest releases is encouraged, provided the exposure window
    and any post-patch improvements are documented.

  - **Professionalism:**

    If citing specific alternatives involves sensitive comparisons, provide
    those names and test pages via private message for our internal reproduction
    to keep the public record focused on technical data.

  - **Stress Testing:**

    Since Drupal core provides native lazy-loading for `IMG` and `IFRAME`,
    your benchmark must include at least one complex media type
    (**VIDEO, AUDIO, or HTML**).

    - Note that only the Blazy ecosystem supports sophisticated mixed-media
      handling; for a fair comparison, ensure alternatives offer equivalent
      handling or separate media types onto different pages.
    - A **20-item sample** is the minimum requirement for a valid benchmark.
    - Most front-end modules are measured by their output, only visible when
      given an extreme burden.

  - **Isolation:**

    Tests must be conducted in **Production mode** with strict isolation
    (no library leaks or global scope pollution).

  - **Objective:**

    Genuine, accountable, legally sound technical corrections with
    considerations to eliminate potential contribution hindrances.

For a different perspective, feel free to choose your own adventure!

### Pro Tips for Accurate Audits:

- Use **Dropzone JS** for local file handling.
- Use a single unlimited **Media field** to easily switch formatters within
  Views blocks to ensure identical server-side burdens.
- To avoid measurement complications from nested field formatters, exclude
  Views-style sliders (Slick Views), Slick Paragraphs or Slick Vanilla in favor
  of direct **Slick Media** formatter implementations for now.

### What are the benefits for you?

**Credits and gratitude.** Every successful contribution or data-driven
disagreement that leads to a code correction is celebrated. We provide
[due credit](https://www.drupal.org/node/2232779/committers) to our
contributors, ensuring your expertise is recognized by the entire community.

---

### The Path to Contribution

If you believe you have identified a genuine flaw, we welcome your issue report.
To respect the community's time, we ask for the following due diligence:

1.  **Define the Flaw:**

    A qualifying flaw is a significant discrepancy between two or more
    benchmarked implementations.

    To distinguish genuine performance flaws from inherent feature costs,
    we adhere to rigorous validation thresholds. Based on the recorded
    benchmarking, a performance regression is only validated by a minimum
    **1500% increase in page weight** or a **100% speed discrepancy**. The
    larger the tested items are, the more significant the discrepancy is.

    Minor variances are recognized as intentional, modular trade-offs for
    advanced functionality. For example, the aesthetic overhead of a "Blur"
    effect requires a negligible ~2kB of JS/CSS. Furthermore, while we provide
    a suite of optional features like a media player, lightbox integration and
    skins, these are configurable assets whose weight is dictated by the user's
    specific creative requirements. Library sizes may vary as well. We classify
    these as feature-driven enhancements, not architectural bloat.

2.  **Document the Setup:**

    Attach comprehensive screenshots of your setup,
    including Blazy/Slick UIs, formatter settings, comparative results,
    accessible pages and **Lighthouse/GT Metrix/CWV** reports.

3.  **Technical Rigor:**

    High effort in your submission ensures high-speed resolution. Reports must
    meet these minimum technical standards to be processed. In short:
    *present Slick's flaws with apples-to-apples benchmarks professionally*.

> **Note on Native Lazy-loading:**
> As of this writing, significant performance discrepancies remain demonstrable
> even with core's or other modules's native lazy-loading. These variances are
> largely dictated by modern **Core Web Vitals** protocols. Achieving mastery of
> these subtleties — specifically the **hidden** and **nested** formatters
> within core site-building architecture — is vital to ensuring your
> contribution is practical and effective. At first sight, Slick is slightly
> heavier given the first visible image is not lazy-loaded, however applying
> identical **Core Web Vitals** rules, no matter how absurd, to all components
> without reservation will properly reveal significant discrepancies. This is
> almost identical to a 10-year-old issue, and such recurring played-out issues
> require a **final, actionable resolution** so we can focus more on the project
> progress and improvement to benefit the wider community.

#### The Accountability Challenge

This project builds for excellence, not the path of least resistance. Recent
discussions regarding decoupling Slick from Blazy overlook a vital reality:
removing Blazy removes the **Core Web Vitals** optimizations (like
sophisticated LCP integration and preloading) that define this project’s
performance edge.

We welcome independent audits. We remain open to the possibility we have
overlooked a relevant factor. Our goal is to move past anecdotal observations
and focus on **verifiable, sound data**. By "serving the data," you help us
maintain a professional environment for the benefit of the entire
Drupal community.

---

### <a name="optimization"></a>Strategic Optimization Checklist

Proper configuration ensures the module works for you, not against you.
Use this checklist to audit your implementation:

#### 1. Essential UI Refinements

- **Optimized Mode:**

  Enable the **Optimized** checkbox in the Slick optionset to strip unnecessary
  bytes.

- **Production Clean-up:**

  Always **uninstall Slick UI** in production; configuration belongs in code
  or exported features.

#### 2. Asset & Resource Management

- **CSS Lean-loading:**

  Disable the core `slick-theme.css` library if using custom icon fonts at
  `/admin/config/media/slick/ui`

- **Lazyload HTML:**

  For third-party embeds (Instagram/Pinterest, etc), enable **Lazyload HTML** in
  the Blazy UI to prevent blocking the main thread.

- **Global Performance:**

  Ensure Drupal’s core **CSS/JS aggregation** and caching are active at
  `/admin/config/development/performance`.

#### 3. Media & Image Engineering

- **Prevent Layout Shift (CLS):**

  Use image styles with a **"crop"** effect whenever possible.
  Select the relevant **Aspect ratio** option in the formatter UI and enable
  **Modern CSS aspect-ratio** (available since Blazy 3.0.17).

- **Loading Priority:**

  Use the **Preload** and **Loading Priority** options for "above-the-fold"
  assets to optimize LCP (Largest Contentful Paint) elements.

- **Responsive Standards:**

  Prioritize Core **Responsive Image** if storage permits. Otherwise, utilize
  formats like **WebP** or **AVIF** with a versatile design.

#### 4. Logic & Interaction Settings

- **Disable Autoplay/Infinite:**

  Unless strictly required, turn these off to prevent early downloads and
  expensive DOM reflows. Only reasonable for text marquees or trivial
  slideshows where performance is not a concern.
  **Autoplay** triggers downloads that can defeat lazy-loading, and **Infinite**
  loops often cause continuous, expensive DOM reflows including duplicate HTTP
  requests due to cloned slides.

- **Grid Strategy:**

  Use **HTML Formatter Grids** instead of JavaScript-based
  **Optionset Grids**. Server-side cached HTML is significantly more performant
  than generating complex DOM trees on-the-fly via JavaScript.

- **Scalability for Galleries:**

  For massive sets, use **Blazy Grid + Lightbox** (Colorbox, PhotoSwipe, etc.).
  This is objectively faster than a Slick-only implementation for static
  viewing, at least until we can make ajaxified Slick Views (3-4-hour backers
  are welcome at Slick Views to move this feature out of premium versions).

#### 5. Additional Optimization Settings & Automated Intelligence
Visit `/admin/config/media/blazy/ui` for further tuning. While Blazy supports
backward compatibility (BC) by default, you should optimize for modern
environments by leveraging both UI options and the module's internal logic:

- **Native Lazyload:**

  Favor native browser lazy-loading (and disable unnecessary polyfills) to
  reduce main-thread execution when targetting modern sites.

- **Lean Markup:**

  Enable **Remove field/view wrapper CSS classes** and ensure
  **"Use theme field"** remains unchecked to reduce DOM depth and "Divitis."

- **Noscript Compatibility:**

  While `<noscript>` tags provide a fallback for users without JavaScript,
  they add extra weight to the HTML. If your target audience is modern
  performance-critical, consider keeping this fallback disabled to shave off
  every possible byte.

- **Admin UIs:**

  Visit `/admin/config/media/blazy` and `/admin/config/media/slick/ui`,
  including Media formatters for more optimization options.
