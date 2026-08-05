![Scout Image Studio banner](assets/banner-1544x500.png)
# Scout Image Studio

![Version](https://img.shields.io/badge/version-2.2.9-blue)
![WordPress](https://img.shields.io/badge/WordPress-7.0.2-blue)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

**AI-powered Media, URL & Metadata Management for WordPress**

Scout Image Studio safely renames physical WordPress image files while keeping generated image sizes, attachment records, URLs, post content, and compatible metadata references synchronized.


## Version 2.2.7

Version 2.2.7 completes the WordPress 6.0 compatibility pass by replacing the WordPress 6.1-only cache-group flush with targeted post-cache invalidation after metadata URL updates.

### Included today

- Physical image-file renaming
- Generated thumbnail and image-size renaming
- WordPress attachment metadata updates
- URL and compatible content-reference updates
- Media Library title synchronization
- Sequential bulk naming with configurable starting number
- Number formats: `1`, `01`, `001`, and `0001`
- Live sequential filename preview
- AI filename suggestions using OpenAI or Google Gemini
- Search, pagination preservation, operation history, undo, and clear history
- Duplicate filename protection
- Up to 50 images per bulk page and 20 images per AI request

## Sequential bulk naming

1. Select the images to rename.
2. Enter a shared base name such as `Scout Trails`.
3. Choose the starting number and number format.
4. Review the live preview.
5. Click **Apply Numbered Names** to fill the filename fields.
6. Review the proposed names and click **Rename Selected Images**.

Example output:

```text
scout-trails-01.jpg
scout-trails-02.jpg
scout-trails-03.jpg
```

## AI naming

Scout Image Studio supports OpenAI and Google Gemini vision models. Images are sent to the configured provider only when **Select Name with AI** is clicked. AI suggestions remain editable and are not applied until the user confirms the rename operation.

API keys are stored in the WordPress options table. Site owners should use a dedicated key with appropriate usage limits and follow their provider's security guidance.

## Safety

Before running large operations, create a current database and uploads backup. Test the plugin on a small group of images first, especially on sites using custom page builders or plugins that store media URLs in proprietary formats.

## Installation

1. Download the plugin ZIP.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload and activate Scout Image Studio.
4. Open **Media → Scout Image Studio**.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- Permission to rename files in the WordPress uploads directory
- An OpenAI or Gemini API key only when AI naming is used

## Roadmap

Planned workspaces include richer metadata editing, alt-text and caption generation, asset health, URL verification, image optimization, duplicate detection, and media SEO insights.

## License

GPL-2.0-or-later


---

## 📜 Changelog

### 2.10.0
- Adds a dedicated laptop and tablet stage between desktop and mobile layouts.
- Moves the history panel below the rename workspace earlier, giving the table more room.
- Keeps every thumbnail at its existing 64 × 64 size.
- Current Filename, New Filename, and Source resize more fluidly.
- More visibility in the middle-width range where the layout previously felt fixed.
- Mobile card layout.

### 2.2.9

- Aligned the API key and model controls in AI & SEO Studio.
- Improved responsive behavior across laptop, tablet, and mobile widths.
- Constrained and balanced AI Studio fields for improved readability.
- Removed the duplicate filename line beneath each New filename field.

## 2.2.7
- More resilient OpenAI and Gemini response parsing for AI filenames.
- Preserves the user's exact scroll position after a rename operation.
- Clearer recoverable AI error messaging.
- Dedicated OpenAI and Google Gemini setup workspace
- Direct links to create provider API keys
- Secure saved-key replacement behavior
- Test Connection workflow
- AI-generated filename suggestions remain reviewable before rename
- Restored premium primary-action sizing


## 2.2.6 — AI Compatibility Engine

- Extracts usable filenames from plain text, JSON, Markdown, quoted text, numbered lists, and explanatory sentences.
- Preserves each image’s original file extension.
- Continues processing successful images when another image needs a retry.
- Adds expandable provider-response diagnostics when extraction fails.
- Vertically centers the Open AI Studio action.
- Adds a direct Back to Scout Image Studio button from AI settings.
- Gives reasoning models a larger visible-output budget and low reasoning effort for filename generation.
- Retries once when OpenAI reports an incomplete response caused by max output tokens.
- Reports incomplete provider responses clearly without changing selected assets.


## 2.2.5 — Scout Image Studio

- Renamed the product throughout WordPress, PHP, documentation, and plugin metadata to **Scout Image Studio**.
- Renamed the plugin folder, bootstrap file, admin slugs, and text domain to `scout-image-studio`.
- Preserved existing settings, AI configuration, rename history, and workflows.


## 2.2.4 — Friendly AI Refinement

- Adds **Generate Another Idea** after AI suggestions are ready.
- Adds **Clear Suggestions** without changing files, URLs, or metadata.
- Uses the success message: **Scout AI has successfully created names for your selected images.**
- Keeps the original AI action as a clear ready-state indicator until suggestions are cleared.


## 2.2.3 — Focused Quick Actions

- Simplified Quick Actions to **Select Name with AI** and **Rename Selected Images**.
- Removed the redundant AI & SEO Studio button from the action bar.
- Added a ready-state confirmation after AI suggestions are generated.
- Increased horizontal padding on the primary rename action.


## 2.2.2 — Quick Actions Polish

- Removed the redundant **Fill Suggested Names** button.
- Added a focused **Quick Actions** workflow for AI naming, AI & SEO settings, and final renaming.
- Matched **Rename Selected Images** to the size and styling of **Apply Numbered Names**.


## 2.2.1 — AI SEO Studio
- Adds Preferred SEO Phrase guidance for AI filename and URL creation.
- Adds optional website context and configurable filename length.
- Uses keywords naturally and avoids keyword stuffing.
- Saves a reusable SEO profile in Scout AI Studio.

---

## ⚖ License

GPL v2 or later

