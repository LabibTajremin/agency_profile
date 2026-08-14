# Content types

Sixteen types, registered by the **plugin**, not the theme. That is deliberate: content
registered by a theme disappears the moment someone switches themes.

| Type             | Menu            | Archive base        |
| ---------------- | --------------- | ------------------- |
| Destination      | Destinations    | `/destinations/`    |
| Institution      | Institutions    | `/institutions/`    |
| Course           | Courses         | `/courses/`         |
| Service          | Services        | `/services/`        |
| Scholarship      | Scholarships    | `/scholarships/`    |
| Test Prep Course | Test Prep       | `/test-prep/`       |
| Event            | Events          | `/events/`          |
| Team Member      | Team            | `/team/`            |
| Testimonial      | Testimonials    | `/testimonials/`    |
| Success Story    | Success Stories | `/success-stories/` |
| Gallery Item     | Gallery         | `/gallery/`         |
| Branch           | Branches        | `/branches/`        |
| Job Opening      | Careers         | `/careers/`         |
| FAQ              | FAQs            | `/faqs/`            |
| Resource         | Resources       | `/resources/`       |
| Partner          | Partners        | `/partners/`        |

Every archive base is filterable, so `/programmes/` instead of `/courses/` is a one-line change
in a child theme.

## Relationships

Eight real post-to-post relationships, stored as IDs rather than as text you have to keep
spelled the same:

- Course → Institution
- Course → Destination (through its institution)
- Institution → Destination
- Scholarship → Destination, Scholarship → Institution
- Success Story → Destination
- Testimonial → Course
- Event → Branch
- Team Member → Branch

Renaming an institution updates every course that points at it, because nothing stored the name.

## Per-item accent

Any item can override the site accent for its own page. Leave it empty to inherit — the same
absence-is-inheritance rule as the rest of the configurator.
