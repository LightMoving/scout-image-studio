![Scout Image Studio](assets/banner-1544x500.png)

# Scout Image Studio
![Version](https://img.shields.io/badge/version-2.2.17-blue)
![WordPress](https://img.shields.io/badge/WordPress-7.0.2%2B-blue)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

<!-- publisher:release:start -->
**AI-powered Media, URL & Metadata Management for WordPress**

Scout Image Studio safely renames physical WordPress image files while keeping generated image sizes, attachment records, URLs, post content, and compatible metadata references synchronized.

## Features

- Safely rename physical image files
- Rename generated thumbnails and intermediate image sizes
- Synchronize WordPress attachment metadata and Media Library titles
- Update image URLs and compatible content references
- Sequential bulk naming with configurable starting numbers
- Number formats: `1`, `01`, `001`, and `0001`
- AI filename suggestions using OpenAI or Google Gemini
- SEO-guided AI naming with preferred keyword and website context
- Review and edit all suggested names before applying them
- Generate another AI idea or clear suggestions without changing files
- Duplicate filename protection
- Rename history, undo, and clear history
- Search, pagination, page-position preservation, and responsive layouts

## Sequential bulk naming

1. Select the images to rename.
2. Enter a shared base name such as `Scout Trails`.
3. Choose the starting number and number format.
4. Review the live preview.
5. Click **Apply Numbered Names** to fill the filename fields.
6. Review the names and click **Rename Selected Images**.

Example:

```text
scout-trails-01.jpg
scout-trails-02.jpg
scout-trails-03.jpg
```

## AI and SEO naming

Scout Image Studio supports optional AI-assisted filename generation through OpenAI and Google Gemini. Images are sent to the selected provider only when an authorized administrator explicitly clicks **Select Name with AI** or **Generate Another Idea**.

AI suggestions remain editable and are never applied until **Rename Selected Images** is clicked. The plugin can use a preferred SEO phrase as guidance while instructing the provider to keep filenames natural and avoid keyword stuffing.

## External services and privacy

Scout Image Studio supports optional AI-assisted filename generation through OpenAI and Google Gemini. Manual and sequential renaming work without either service.

No image or media content is sent automatically. A selected image is transmitted only after an authorized administrator explicitly chooses **Select Name with AI** or **Generate Another Idea**.

For AI naming, Scout Image Studio sends the selected image itself (base64-encoded), filename-generation instructions, and contextual information used to guide the suggestion. Context can include the attachment title, alt text, parent-post title, preferred SEO phrase, website context, and configured maximum filename length. Requests go directly from the WordPress site to the provider selected by the administrator; Scout does not proxy them through a Scout-operated service.

**OpenAI** is used when OpenAI is configured as the provider. The plugin may contact the OpenAI model endpoint when testing the connection and the Responses API when generating a filename suggestion.

- OpenAI Services Agreement: https://openai.com/policies/services-agreement/
- OpenAI Privacy Policy: https://openai.com/policies/privacy-policy/

**Google Gemini** is used when Gemini is configured as the provider. The plugin may contact the Gemini model endpoint when testing the connection and the Gemini generateContent endpoint when generating a filename suggestion.

- Gemini API Additional Terms of Service: https://ai.google.dev/gemini-api/terms
- Google Privacy Policy: https://policies.google.com/privacy

API keys remain stored in the site's WordPress options table and are sent only to the selected provider as required to authenticate administrator-requested operations. Site owners are responsible for the provider's applicable account requirements, terms, privacy practices, billing, quotas, and usage policies.

## Source code and development

The complete public source repository is available at:
https://github.com/LightMoving/scout-image-studio

The PHP, CSS, and JavaScript distributed with Scout Image Studio are human-readable source files. This release does not require a JavaScript or CSS compilation/minification build step to reproduce the distributed assets.

## Installation

1. Download the plugin ZIP.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload and activate Scout Image Studio.
4. Open **Media → Scout Image Studio**.
5. Create a current database and uploads backup before large rename operations.

## Requirements

- WordPress 7.1 or newer
- PHP 7.4 or newer
- Permission to modify files in the WordPress uploads directory
- An OpenAI or Google Gemini API key only when AI naming is used

## Frequently asked questions

### Does Scout Image Studio rename the physical file?

Yes. It renames the physical image and its generated image sizes, then updates the associated WordPress metadata.

### Does it update image URLs and references?

Yes. Scout updates the attachment data and compatible references it can identify. A current backup is still strongly recommended, especially on sites using custom page builders or proprietary storage formats.

### Can a rename be undone?

Yes. Recent rename operations are recorded in the history panel and can be undone.

### Is AI required?

No. Manual naming and sequential bulk naming work without an AI provider or API key.

### When are images sent to an AI provider?

Only after an authorized administrator explicitly requests AI naming.

## Safety

Before running large operations, create a current database and uploads backup. Test the plugin on a small group of images first, especially on sites using custom page builders or plugins that store media URLs in proprietary formats.

## Changelog

### 2.2.17

Change: Responsiveness & Compatibility
### 2.2.16

Publisher Payload: `0de15b9e82f784fa…`

Release packaged and verified with WordPress Plugin Publisher.

### 2.2.15

- Performance Upgrades

### 2.2.10

- Added a dedicated laptop and tablet layout between desktop and mobile widths.
- Moves the history panel below the rename workspace earlier to preserve table room.
- Keeps thumbnail dimensions unchanged while allowing filename columns to resize fluidly.
- Improved visibility of the Source and New filename columns at intermediate widths.

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

## License

GPL-2.0-or-later
