# Structured data / JSON-LD (REQUIREMENT §9.4)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 9.4 Structured data (`SEO-SD`)

Built with `spatie/schema-org` 3.x (typed builders) into one `@graph` per page. Validation: QA-SEO-06.

| ID | Page type | Types and key properties |
|---|---|---|
| SEO-SD-001 | All pages | `Organization` (`@id` `{site}/#organization`): name, legalName "Gemcon City Limited", url, logo (ImageObject), sameAs (social URLs), parentOrganization (`Organization` "Gemcon Group" with url), contactPoint (telephone, contactType "sales", areaServed "BD", availableLanguage ["en","bn"]) |
| SEO-SD-002 | All pages | `WebSite` (`@id` `{site}/#website`): name, url, publisher → organization, potentialAction `SearchAction` target `{site}/search?q={search_term_string}`, query-input `required name=search_term_string`. (Google retired the sitelinks search box display in 2024; the markup stays as valid site metadata.) |
| SEO-SD-003 | Home, Contact | `RealEstateAgent` (a `LocalBusiness` subtype, the closest schema.org type for a developer's sales office) per office: name, address (`PostalAddress`, addressCountry "BD"), geo, telephone, openingHoursSpecification, url, image, priceRange "৳৳৳", parentOrganization → organization |
| SEO-SD-004 | All except home | `BreadcrumbList` matching CMP-BREAD-01 |
| SEO-SD-005 | Project detail | `RealEstateListing` (the page) with name, url, datePosted (first publish), dateModified, image, `mainEntity` → the property node. Property node: residential and mixed-use = `ApartmentComplex`; commercial = `Place`; land = `Place`. Properties: name, description, address, geo, numberOfAccommodationUnits (residential), amenityFeature (`LocationFeatureSpecification` name + value true), photo, `additionalProperty` (`PropertyValue`) for land area (unitText "katha"), storeys, RAJUK approval no. and handover date; containsPlace → one node per unit type: residential = `Apartment` (name, floorSize `QuantitativeValue` value + unitCode "FTK", numberOfBedrooms, numberOfBathroomsTotal, numberOfRooms); commercial = `Accommodation` with `accommodationCategory` "Office" or "Retail" and floorSize; land = none. |
| SEO-SD-006 | Project detail with visible price | Separate `Offer` node per priced unit type: itemOffered → that `Apartment` `@id`, price (number), priceCurrency "BDT", availability (`InStock` = available/limited, `SoldOut`), seller → organization. Emitted only when the price is visible on the page (AC-PROJ-01). |
| SEO-SD-007 | Why not Product/Offer for the listing | Google's Product/merchant rich results expect purchasable goods with prices and reviews; most projects hide prices, which would produce invalid or misleading Product markup. `RealEstateListing` + residence types describe the page truthfully; no rich result is promised (documented in DEC-12). |
| SEO-SD-008 | Location pages | `CollectionPage` with `about` → `Place` (area name, geo, containedInPlace → city `City`), `mainEntity` → `ItemList` of project `RealEstateListing` URLs (position, url, name) |
| SEO-SD-009 | Project listing / facet pages | `CollectionPage` + `ItemList` of the current page's projects |
| SEO-SD-010 | News article | `BlogPosting` (or `NewsArticle` when category = News/Press releases): headline, description, image (≥ 1200 px, 16:9 and 1:1 conversions), datePublished, dateModified, author (`Person` with url to team page, or organization), publisher → organization, mainEntityOfPage |
| SEO-SD-011 | Testimonials | No `Review`/`AggregateRating` markup (self-serving reviews of the site's own organization are ineligible for review snippets) |
| SEO-SD-012 | FAQ page and FAQ blocks | `FAQPage` with `Question`/`acceptedAnswer` `Answer`. Google shows FAQ rich results only for government and health sites since 2023; markup kept for semantic clarity, no rich result expected |
| SEO-SD-013 | Leadership page | `Person` per leader (name, jobTitle, image, worksFor → organization, sameAs LinkedIn) inside an `ItemList` |
| SEO-SD-014 | Careers detail | `JobPosting`: title, description (HTML), datePosted, validThrough (deadline 23:59 Asia/Dhaka), employmentType (FULL_TIME/PART_TIME/CONTRACTOR/INTERN), hiringOrganization, jobLocation (`Place` with `PostalAddress`), baseSalary only if salary given as a number range, directApply true |
| SEO-SD-015 | Video block / project video | `VideoObject`: name, description, thumbnailUrl, uploadDate, contentUrl (upload) or embedUrl (YouTube), duration (ISO 8601 when known) |

**JSON-LD examples**

Home (abridged `@graph`):

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://www.gemconcity.com/#organization",
      "name": "Gemcon City Limited",
      "legalName": "Gemcon City Limited",
      "url": "https://www.gemconcity.com/",
      "logo": { "@type": "ImageObject", "url": "https://www.gemconcity.com/storage/media/1/logo-gemcon-city.png", "width": 600, "height": 138 },
      "sameAs": ["https://www.facebook.com/gemconcity", "https://www.linkedin.com/company/gemconcity"],
      "parentOrganization": { "@type": "Organization", "name": "Gemcon Group", "url": "https://web.gemcon.org/" },
      "contactPoint": [{ "@type": "ContactPoint", "telephone": "+8809600000000", "contactType": "sales", "areaServed": "BD", "availableLanguage": ["en", "bn"] }]
    },
    {
      "@type": "WebSite",
      "@id": "https://www.gemconcity.com/#website",
      "url": "https://www.gemconcity.com/",
      "name": "Gemcon City",
      "publisher": { "@id": "https://www.gemconcity.com/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "https://www.gemconcity.com/search?q={search_term_string}" },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "RealEstateAgent",
      "@id": "https://www.gemconcity.com/#office-dhaka",
      "name": "Gemcon City Limited – Head Office",
      "image": "https://www.gemconcity.com/storage/media/12/head-office.jpg",
      "telephone": "+8809600000000",
      "priceRange": "৳৳৳",
      "address": { "@type": "PostalAddress", "streetAddress": "House 00, Road 00", "addressLocality": "Dhaka", "postalCode": "1212", "addressCountry": "BD" },
      "geo": { "@type": "GeoCoordinates", "latitude": 23.7925, "longitude": 90.4078 },
      "openingHoursSpecification": [{ "@type": "OpeningHoursSpecification", "dayOfWeek": ["Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday"], "opens": "09:30", "closes": "18:30" }],
      "parentOrganization": { "@id": "https://www.gemconcity.com/#organization" }
    }
  ]
}
```

Project detail (residential, one priced unit type):

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "RealEstateListing",
      "@id": "https://www.gemconcity.com/projects/gemcon-orchid#listing",
      "url": "https://www.gemconcity.com/projects/gemcon-orchid",
      "name": "Gemcon Orchid – 3–4 Bed Apartments in Bashundhara R/A",
      "datePosted": "2026-11-02",
      "dateModified": "2026-11-20",
      "image": ["https://www.gemconcity.com/storage/media/40/gemcon-orchid-front-elevation-1920.webp"],
      "mainEntity": { "@id": "https://www.gemconcity.com/projects/gemcon-orchid#property" },
      "breadcrumb": { "@id": "https://www.gemconcity.com/projects/gemcon-orchid#breadcrumb" }
    },
    {
      "@type": "ApartmentComplex",
      "@id": "https://www.gemconcity.com/projects/gemcon-orchid#property",
      "name": "Gemcon Orchid",
      "description": "A 2B+G+9 residential building of 36 apartments in Block D, Bashundhara R/A.",
      "address": { "@type": "PostalAddress", "streetAddress": "Plot 00, Road 00, Block D", "addressLocality": "Bashundhara R/A, Dhaka", "addressCountry": "BD" },
      "geo": { "@type": "GeoCoordinates", "latitude": 23.8191, "longitude": 90.4320 },
      "numberOfAccommodationUnits": 36,
      "amenityFeature": [
        { "@type": "LocationFeatureSpecification", "name": "Rooftop garden", "value": true },
        { "@type": "LocationFeatureSpecification", "name": "Gymnasium", "value": true }
      ],
      "containsPlace": [
        {
          "@type": "Apartment",
          "@id": "https://www.gemconcity.com/projects/gemcon-orchid#unit-type-a",
          "name": "Type A",
          "floorSize": { "@type": "QuantitativeValue", "value": 1850, "unitCode": "FTK" },
          "numberOfBedrooms": 3,
          "numberOfBathroomsTotal": 3,
          "numberOfRooms": 5
        }
      ]
    },
    {
      "@type": "Offer",
      "itemOffered": { "@id": "https://www.gemconcity.com/projects/gemcon-orchid#unit-type-a" },
      "price": 14500000,
      "priceCurrency": "BDT",
      "availability": "https://schema.org/InStock",
      "seller": { "@id": "https://www.gemconcity.com/#organization" }
    },
    {
      "@type": "BreadcrumbList",
      "@id": "https://www.gemconcity.com/projects/gemcon-orchid#breadcrumb",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.gemconcity.com/" },
        { "@type": "ListItem", "position": 2, "name": "Projects", "item": "https://www.gemconcity.com/projects" },
        { "@type": "ListItem", "position": 3, "name": "Gemcon Orchid" }
      ]
    }
  ]
}
```

Location page:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "CollectionPage",
      "url": "https://www.gemconcity.com/locations/bashundhara-ra",
      "name": "Flats & Commercial Spaces in Bashundhara R/A, Dhaka",
      "about": { "@type": "Place", "name": "Bashundhara R/A", "geo": { "@type": "GeoCoordinates", "latitude": 23.8193, "longitude": 90.4526 }, "containedInPlace": { "@type": "City", "name": "Dhaka" } },
      "mainEntity": {
        "@type": "ItemList",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "url": "https://www.gemconcity.com/projects/gemcon-orchid", "name": "Gemcon Orchid" }
        ]
      }
    }
  ]
}
```

Project listing / facet page (`/projects/status/ongoing`, page 1):

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "CollectionPage",
      "url": "https://www.gemconcity.com/projects/status/ongoing",
      "name": "Ongoing Projects in Bangladesh",
      "mainEntity": {
        "@type": "ItemList",
        "numberOfItems": 12,
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "url": "https://www.gemconcity.com/projects/gemcon-orchid", "name": "Gemcon Orchid" },
          { "@type": "ListItem", "position": 2, "url": "https://www.gemconcity.com/projects/gemcon-tower", "name": "Gemcon Tower" }
        ]
      }
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.gemconcity.com/" },
        { "@type": "ListItem", "position": 2, "name": "Projects", "item": "https://www.gemconcity.com/projects" },
        { "@type": "ListItem", "position": 3, "name": "Ongoing" }
      ]
    }
  ]
}
```

Project video (added to the project `@graph` when a video exists):

```json
{
  "@type": "VideoObject",
  "name": "Gemcon Orchid – project walkthrough",
  "description": "Exterior and amenity walkthrough of Gemcon Orchid, Bashundhara R/A.",
  "thumbnailUrl": "https://www.gemconcity.com/storage/media/41/gemcon-orchid-video-poster-1280.webp",
  "uploadDate": "2026-11-02T10:00:00+06:00",
  "embedUrl": "https://www.youtube-nocookie.com/embed/VIDEO_ID",
  "duration": "PT2M14S"
}
```

Contact page: the Organization and `RealEstateAgent` nodes from the home example (one `RealEstateAgent` per office) plus `BreadcrumbList`.

News article:

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "What to check before buying a flat in Dhaka",
  "description": "A checklist of approvals, documents and site checks for first-time buyers.",
  "image": ["https://www.gemconcity.com/storage/media/88/buying-checklist-1200x675.webp", "https://www.gemconcity.com/storage/media/88/buying-checklist-1200x1200.webp"],
  "datePublished": "2026-11-10T10:00:00+06:00",
  "dateModified": "2026-11-12T09:00:00+06:00",
  "author": { "@type": "Person", "name": "Author Name", "url": "https://www.gemconcity.com/about/leadership#author-name" },
  "publisher": { "@id": "https://www.gemconcity.com/#organization" },
  "mainEntityOfPage": "https://www.gemconcity.com/news/what-to-check-before-buying-a-flat-in-dhaka"
}
```

FAQ page:

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    { "@type": "Question", "name": "Is the project approved by RAJUK?", "acceptedAnswer": { "@type": "Answer", "text": "Yes. The approval number is listed on each project page under At a glance." } }
  ]
}
```

Leadership:

```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "item": { "@type": "Person", "name": "Leader Name", "jobTitle": "Managing Director", "image": "https://www.gemconcity.com/storage/media/5/leader.webp", "worksFor": { "@id": "https://www.gemconcity.com/#organization" }, "sameAs": ["https://www.linkedin.com/in/example"] } }
  ]
}
```

Job posting:

```json
{
  "@context": "https://schema.org",
  "@type": "JobPosting",
  "title": "Senior Sales Executive",
  "description": "<p>Manage enquiries for residential projects…</p>",
  "datePosted": "2026-11-01",
  "validThrough": "2026-11-30T23:59:00+06:00",
  "employmentType": "FULL_TIME",
  "hiringOrganization": { "@id": "https://www.gemconcity.com/#organization" },
  "jobLocation": { "@type": "Place", "address": { "@type": "PostalAddress", "addressLocality": "Dhaka", "addressCountry": "BD" } },
  "directApply": true
}
```

**Validation steps (QA-SEO-06):** (1) CI: a Pest test renders the `seo.jsonLd` prop for one fixture of every page type, parses JSON, and asserts required properties per table above; (2) pre-launch: run every page type through Google Rich Results Test and the Schema Markup Validator (validator.schema.org), zero errors; (3) post-launch: GSC Enhancements reports reviewed weekly for 4 weeks.
