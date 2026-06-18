# Admin Revenue Report — Design Spec

**Date:** 2026-06-18
**Scope:** Core revenue analysis page for admin role (v1)
**Status:** Approved

---

## 1. Overview

A new admin page at `/admin/reports` that lets admin staff analyse paid package revenue over a selectable time window. Reads from existing `transactions`, `packages`, and `locations` tables — no schema changes required.

---

## 2. Route & Component

| Artefact | Path |
|---|---|
| Livewire component | `app/Livewire/Admin/Reports.php` |
| Blade view | `resources/views/livewire/admin/reports.blade.php` |
| Route name | `admin.reports` |
| URL | `/admin/reports` |
| Sidebar section | Finance — below Payments |

Route registered in `routes/web.php` inside the existing `admin` middleware group, following the same pattern as `admin.payments`.

---

## 3. Revenue Definition

- **Revenue = transactions with `status = 'paid'` only.** Pending / rejected / expired are excluded from all revenue figures.
- **Date basis = `paid_at`** — the timestamp when money was actually received. This is used for the KPI totals, the over-time chart, and all breakdowns.
- **Conversion funnel** uses `created_at` (when a transaction was initiated) so the denominator is fair.

---

## 4. Filter Bar

Persisted as Livewire public properties, all `wire:model.live` so every change instantly recomputes.

| Property | Type | Default | Description |
|---|---|---|---|
| `$preset` | string | `'month'` | Active preset: `month` / `30d` / `year` / `custom` |
| `$dateFrom` | string | start of current month | ISO date string |
| `$dateTo` | string | today | ISO date string |
| `$filterLocation` | int\|null | `null` | Location ID, null = all |

**Presets**
- **Bulan Ini** — `startOfMonth()` → today
- **30 Hari** — `subDays(29)` → today
- **Tahun Ini** — `startOfYear()` → today
- **Custom** — shows two `<input type="date">` fields; activated automatically when either date is edited directly

If `$dateTo < $dateFrom`, they are silently swapped before querying.

Location dropdown populated from `Location::where('is_active', true)->orderBy('name')`.

---

## 5. Page Layout (top → bottom)

### 5.1 KPI Cards (4 cards, 2×2 on mobile / 4-col on desktop)

| Card | Calculation |
|---|---|
| Total Revenue | `SUM(amount)` on paid transactions in range |
| Transactions Paid | `COUNT(*)` on paid transactions in range |
| Avg per Transaction | Total Revenue ÷ Transactions Paid (0 if none) |
| Conversion Rate | Paid ÷ total transactions created in range × 100% |

Cards use existing `<x-card>` component. Monetary values formatted as `Rp X.XXX.XXX` (Indonesian locale, `number_format($v, 0, ',', '.')`).

### 5.2 Revenue Over Time (SVG bar chart)

- **Buckets:** ≤ 31-day range → daily; > 31 days → monthly.
- Rendered entirely server-side as an inline `<svg>`. Bar heights scaled to the max bucket value. If max = 0, all bars rendered at height 0 (no division).
- **Hover tooltip:** Alpine `x-data` on each bar group; `x-on:mouseenter` shows a small tooltip (date label + formatted amount). Alpine is already in the project.
- Y-axis: 4 evenly spaced gridlines with formatted labels. X-axis: date labels (pruned to avoid overlap — show every Nth label based on bucket count).
- Empty state: if all buckets are zero, replace chart with `<x-empty-state>`.

### 5.3 Revenue by Package Type (horizontal bars)

Package types: `registration` · `regular` · `drop_in` · `private`

Each row: type label (with colour dot matching the packages page) · revenue bar · formatted amount · transaction count · percentage of total. Uses same `typeMeta` colour scheme as `admin/packages.blade.php`.

### 5.4 Revenue by Location (horizontal bars)

Same horizontal bar pattern as 5.3. One row per location with paid transactions in range. Sorted by revenue descending.

### 5.5 Top Packages (table, top 10)

Columns: Package Name · Type (badge) · Location · Units Sold · Total Revenue. Sorted by Total Revenue descending, limited to 10 rows. Uses `<x-badge>` for type.

### 5.6 Payment Funnel (status strip)

Four segments in one row: **Paid · Pending · Rejected · Expired**. Counts from transactions created in range (`created_at`). Each segment shows count + percentage of total. Colour: paid = green, pending = amber, rejected = red, expired = grey — matching existing badge colours used on the payments page.

---

## 6. Data Queries

Two queries, both scoped to `$dateFrom`–`$dateTo` and optional `$filterLocation` via `package.location_id`.

**Query A — paid transactions (revenue)**
```php
Transaction::where('status', 'paid')
    ->whereBetween('paid_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->when($filterLocation, fn($q) => $q->whereHas('package', fn($p) => $p->where('location_id', $filterLocation)))
    ->with(['package.location'])
    ->get()
```
All KPI cards 5.1, charts 5.2–5.4, and top packages 5.5 derived from this one collection in PHP — no extra queries.

**Query B — all-status funnel (conversion)**
```php
Transaction::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->when($filterLocation, ...)
    ->selectRaw('status, COUNT(*) as cnt')
    ->groupBy('status')
    ->get()
```

No N+1 issues. Both queries are resolved inside `render()`.

---

## 7. Empty & Edge States

| Situation | Behaviour |
|---|---|
| No paid transactions in range | KPIs show 0 / Rp 0, charts replaced by `<x-empty-state>`, tables show "Belum ada data" |
| Single data point in chart | Valid — renders a single bar |
| `$dateTo < $dateFrom` | Swapped silently before querying |
| Location has no paid transactions | Omitted from location bars and top packages |

---

## 8. Sidebar Change

Add one `<x-sidebar-link>` to `resources/views/components/admin-nav.blade.php` in the **Finance** section, after the Payments link. Icon: a bar-chart SVG (consistent with existing icon style — 24×24, stroke-width 2, no fill).

---

## 9. Testing

Feature test in `tests/Feature/Admin/ReportsTest.php`:

1. Seed: 3 paid transactions + 1 pending + 1 rejected, spanning two months, two locations.
2. Assert default (current month) KPIs count only paid transactions with `paid_at` in range.
3. Assert location filter narrows results to correct subset.
4. Assert conversion rate = paid count ÷ total created in range.
5. Assert `$dateTo < $dateFrom` swap produces valid (non-empty) results.

---

## 10. Out of Scope (v1)

- Export to CSV / PDF
- New vs returning buyers
- Churn / renewal watch list
- Top-spending parents
