# Payment & Subscription Model — Eskoofy Products

> Purpose: Define pricing strategy for Eskoofy products on the branding/website
> Date: September 2026

---

## Product Overview

Eskoofy has 3 products, each with distinct deployment models:

| Product | Deployment | Target | Variant |
|---------|-----------|--------|---------|
| `eskoofy-app` | Self-hosted Laravel | Schools wanting full control | BD + INT |
| `eskoofy-theme` | WordPress plugin-theme hybrid | Schools already on WordPress | INT primary |
| `eskoofy-php` | Self-hosted raw PHP | Low-resource hosting environments | INT primary |

---

## Recommended Pricing Strategy

### Model: **Freemium + Tiered SaaS** (Hybrid)

This combines the strengths of openSIS (public tiers), Fedena (flat-rate), and Gibbon (free tier) models.

### Why This Model

1. **Free tier** eliminates adoption friction (like Gibbon)
2. **Public pricing** builds trust (like openSIS/Fedena)
3. **Self-hosted option** appeals to schools wanting data control
4. **Cloud-hosted option** generates recurring revenue
5. **Feature gating** allows upsell without locking out small schools

---

## Pricing Tiers

### Tier 1: **Community** (Free)

| Feature | Included |
|---------|----------|
| Students | Up to 100 |
| Teachers | Up to 20 |
| Classes | Up to 10 |
| Core modules | Student management, attendance, basic exams, fees |
| Support | Community forums only |
| Updates | Manual (self-hosted) |
| Deployment | Self-hosted only |
| Branding | "Powered by Eskoofy" footer badge |

**Purpose:** Adoption, market penetration, word-of-mouth growth

---

### Tier 2: **School** ($29/month or $290/year)

| Feature | Included |
|---------|----------|
| Students | Up to 500 |
| Teachers | Up to 50 |
| Classes | Unlimited |
| Core modules | Everything in Community |
| Advanced modules | HR & payroll, transport, hostel, library, SMS gateway |
| Support | Email support (48h response) |
| Updates | Auto-updates (cloud) / notification (self-hosted) |
| Deployment | Cloud or self-hosted |
| Branding | No "Powered by" badge |
| Reports | Advanced analytics & report builder |
| API | Read-only API access |

**Purpose:** Revenue from small-medium schools

---

### Tier 3: **District** ($79/month or $790/year)

| Feature | Included |
|---------|----------|
| Students | Up to 2,000 |
| Teachers | Unlimited |
| Classes | Unlimited |
| Core modules | Everything in School |
| Premium modules | Custom certificates, bulk SMS campaigns, payment gateway integration, multi-campus |
| Support | Priority email + chat support (24h response) |
| Updates | Auto-updates |
| Deployment | Cloud or self-hosted |
| Branding | White-label ready |
| Reports | Report builder + export + scheduled reports |
| API | Full API access |
| Multi-campus | Up to 3 campuses |

**Purpose:** Revenue from medium-large schools

---

### Tier 4: **Enterprise** (Custom pricing)

| Feature | Included |
|---------|----------|
| Students | Unlimited |
| Teachers | Unlimited |
| Classes | Unlimited |
| Core modules | Everything in District |
| Premium modules | All modules + custom development |
| Support | Dedicated account manager + phone support (4h response) |
| Updates | Priority updates + custom features |
| Deployment | Cloud, self-hosted, or on-premise |
| Branding | Full white-label |
| Reports | Custom report builder + BI integration |
| API | Full API + webhooks + custom integrations |
| Multi-campus | Unlimited campuses |
| SLA | 99.9% uptime guarantee |
| Training | On-site training included |

**Purpose:** Revenue from large districts and international school networks

---

## Payment Methods by Variant

### BD (Bangladesh) Variant

| Method | Details |
|--------|---------|
| bKash | Primary mobile payment |
| Rocket | Secondary mobile payment |
| Nagad | Tertiary mobile payment |
| Bank Transfer | Manual verification |
| Cash | In-person payment at school |

### INT (International) Variant

| Method | Details |
|--------|---------|
| Stripe | Credit/debit cards (primary) |
| PayPal | Alternative for schools without card access |
| Paddle | Subscription billing with tax handling |
| Bank Transfer | Wire transfer for Enterprise |

---

## Subscription Management Features

### For Schools (Customers)

1. **Dashboard:** View current plan, usage, billing history
2. **Upgrade/Downgrade:** Instant tier change with prorated billing
3. **Payment History:** Download invoices and receipts
4. **License Keys:** Activate/deactivate per installation
5. **Auto-renewal:** Toggle on/off, reminders before renewal
6. **Cancellation:** Self-service with retention offer

### For Eskoofy (Admin)

1. **License Server:** `eskoofy-website` already has `/api/v1/licenses/*` endpoints
2. **Customer Management:** Admin panel for customers, licenses, payments
3. **Usage Tracking:** Monitor student/teacher counts per license
4. **Revenue Dashboard:** MRR, ARR, churn rate, LTV
5. **Dunning:** Automated retry for failed payments

---

## Free Trial Strategy

| Aspect | Recommendation |
|--------|---------------|
| Duration | 14 days (like Fedena) |
| Tier | Full District tier features |
| Credit card required? | No (reduce friction) |
| Post-trial | Downgrade to Community tier, data preserved |
| Reminder emails | Day 3, Day 7, Day 13, Day 14 |
| Extension | Allow 7-day extension on request |

---

## Revenue Projections (Conservative)

### Year 1 Targets

| Tier | Schools | Monthly Revenue | Annual Revenue |
|------|---------|----------------|----------------|
| Community | 500 | $0 | $0 |
| School | 50 | $1,450 | $17,400 |
| District | 10 | $790 | $9,480 |
| Enterprise | 2 | Custom ($150 avg) | $3,600 |
| **Total** | **562** | **$2,240+** | **$30,480+** |

### Growth Assumptions
- 500 free tier schools in Year 1 (viral/organic)
- 10% free-to-paid conversion rate
- Average revenue per paid school: $50/month
- Enterprise pricing starts at $150/month average

---

## Implementation Roadmap

### Phase 1: Foundation (Month 1-2)
- [ ] Set up Stripe integration in `eskoofy-website`
- [ ] Implement license activation/validation flow
- [ ] Create pricing page on branding site
- [ ] Add plan management in customer dashboard

### Phase 2: Launch (Month 3)
- [ ] Launch with Community + School tiers
- [ ] 14-day free trial for School tier
- [ ] Payment method integration (Stripe + bKash for BD)

### Phase 3: Growth (Month 4-6)
- [ ] Add District tier
- [ ] Implement usage tracking
- [ ] Add Paddle for international schools
- [ ] Launch referral program

### Phase 4: Enterprise (Month 7+)
- [ ] Add Enterprise tier
- [ ] Custom invoicing
- [ ] On-premise licensing
- [ ] SLA management

---

## Key Decisions Needed

1. **Staff-based vs Student-based pricing?**
   - Recommendation: Student-based (more intuitive for schools)
   - openSIS uses staff-based as differentiator, but most competitors use student-based

2. **Self-hosted pricing?**
   - Option A: Same price as cloud (simpler)
   - Option B: Discount for self-hosted (reduced infrastructure cost)
   - Recommendation: Same price (Option A) — value is in the software, not hosting

3. **BD variant pricing?**
   - Option A: Same USD pricing
   - Option B: BDT equivalent at market rate
   - Recommendation: BDT pricing at market rate (localize for BD market)

4. **Open-source components?**
   - Community tier is effectively open-source
   - Source code included in Enterprise tier only (like Fedena)
