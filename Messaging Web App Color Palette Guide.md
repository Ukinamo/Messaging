# Messaging Web App Color Palette Guide

Based on your selected logo, I have developed a professional color palette designed for high legibility, modern aesthetics, and consistent branding across your web application.

## Core Palette

| Category | Name | Hex Code | Usage Recommendation |
| :--- | :--- | :--- | :--- |
| **Primary** | Deep Indigo | `#1C1A85` | Main branding, navigation bars, primary buttons, and active states. |
| **Secondary** | Electric Purple | `#6427CF` | Secondary buttons, highlights, icons, and hover effects. |
| **Accent** | Soft Lavender | `#A58BD7` | Subtle borders, light backgrounds for cards, and decorative elements. |
| **Background** | Off-White / Pearl | `#F4F2F9` | Main application background to reduce eye strain and provide contrast. |
| **Text / Dark** | Deep Charcoal | `#24222E` | Primary body text, headings, and dark-mode elements. |

## Implementation

### Files

| File | Role |
| :--- | :--- |
| `resources/css/messaging-design-system.css` | **Zguide design system**: CSS variables (`--primary-indigo`, `--background-pearl`, …), spacing/shadows, `.btn`, `.card`, `.navbar`, `.hero`, `.features`, `.cta`, `footer`, utilities. |
| `resources/css/app.css` | Tailwind v4, `@theme` (maps **Inter** + **Poppins**), shadcn semantic tokens (`--background`, `--primary`, …) mapped to those variables, `.dark` overrides. |
| `resources/views/app.blade.php` | Loads Google Fonts (Inter + Poppins); inline shell background matches pearl / dark. |

### Typography

- **Body:** Inter (`400`, `500`, `600`)
- **Headings (global h1–h6):** Poppins (`600`, `700`, `800`) — see `messaging-design-system.css`

Use Tailwind `font-heading` where you add the heading font stack in Vue (e.g. `class="font-heading"`).

### Dark mode

`app.css` `.dark` applies to the Inertia app shell. Marketing classes in `messaging-design-system.css` include `.dark` overrides for `.navbar`, `.card`, forms, and headings so **Zguide** pages stay consistent.

## Implementation Tips

### 1. Contrast and Accessibility

- Use **Deep Charcoal** (`#24222E`) for primary text on **Pearl** (`#F4F2F9`) — implemented as `--text-charcoal` / `--background-pearl`.
- Primary buttons use the **indigo → purple** gradient (`.btn-primary`).

### 2. UI Elements

- **Navigation:** Deep Indigo anchors the design; **Electric Purple** for active/hover accents.
- **Cards and Containers:** Soft Lavender and muted gray borders (`--muted-gray`).

### 3. Dark Mode

- App shell: warm dark background `#1E1D26`, soft text `#E8E6EF`, adjusted primary violet for contrast.
