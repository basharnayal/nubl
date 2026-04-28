<?php

namespace App\Support;

use App\Models\Request as RequestModel;

/**
 * View-model for the recipient request **status card** on the request detail page.
 *
 * The UI uses one Blade partial for every request (`request-status-card`). This class is the
 * single source of truth for how that card should look for a given {@see RequestModel::$status}:
 * accent palette, hero icon, translation keys for title/subtitle, status badge, the four-step
 * progress row, and which footer variant to show (redeem QR, fulfilled note, or default actions).
 *
 * **Steps (four columns):** Each entry has `state` (`done`, `current`, or `pending`) and
 * `hintKey` (translation key for the line under the step). The view turns `state` into filled
 * circles, emphasis, and connector-line styling. {@see self::STEP_LABELS} provides the four
 * step titles (translation keys). The exact business meaning of each step (submitted → review →
 * redeem → fulfilled, etc.) is encoded per status in the private builders below.
 *
 * **Footer:** `footer` selects optional blocks in the partial:
 * - `requested` — primary CTAs after submit (e.g. browse providers) plus default links.
 * - `redeem` — redemption / QR area when the request is approved or redeemable.
 * - `fulfilled` — short closing note for completed orders.
 * - `simple` — no redeem block; standard “back home” + “request history” buttons.
 *
 * **Status routing:**
 *
 * | {@see RequestModel::$status} | Card builder |
 * |-------------------------------|--------------|
 * | `REQUESTED` | {@see self::requested()} |
 * | `APPROVED`, `REDEEMABLE` | {@see self::redeemReady()} |
 * | `FULFILLED` | {@see self::fulfilled()} |
 * | `REJECTED`, `ADMIN_REJECTED` | {@see self::rejected()} |
 * | `CANCELLED` | {@see self::cancelled()} |
 * | `ADMIN_PENDING` | {@see self::adminPending()} |
 * | `ADMIN_APPROVED` | {@see self::adminApproved()} |
 * | anything else | {@see self::fallback()} |
 */
class RecipientRequestStatusPresenter
{
    /**
     * Fixed four-step titles for the progress row (keys into lang files).
     *
     * @var list<array{label: string}>
     */
    public const STEP_LABELS = [
        ['label' => 'recipient.request_progress.step1'],
        ['label' => 'recipient.request_progress.step2'],
        ['label' => 'recipient.request_progress.step3'],
        ['label' => 'recipient.request_progress.step4'],
    ];

    /**
     * Build the card configuration for the current request (used by the detail view).
     *
     * @param  bool  $justSubmitted  When status is `REQUESTED`, whether the user arrived
     *                               immediately after submitting (affects hero copy and step hints).
     * @return array{
     *   accent: string,
     *   heroIcon: 'check'|'clock'|'x'|'ban',
     *   titleKey: string,
     *   subtitleKey: string,
     *   badgeClass: string,
     *   badgeLabelKey: string|null,
     *   badgeStatusRaw?: string,
     *   steps: list<array{state: 'done'|'current'|'pending', hintKey?: string}>,
     *   footer: 'requested'|'redeem'|'fulfilled'|'simple',
     * }
     */
    public static function card(RequestModel $request, bool $justSubmitted): array
    {
        return match ($request->status) {
            'REQUESTED' => self::requested($justSubmitted),
            'APPROVED', 'REDEEMABLE' => self::redeemReady($request),
            'FULFILLED' => self::fulfilled(),
            'REJECTED' => self::rejected(),
            'ADMIN_REJECTED' => self::adminRejected(),
            'CANCELLED' => self::cancelled(),
            'ADMIN_PENDING' => self::adminPending(),
            'ADMIN_APPROVED' => self::adminApproved(),
            default => self::fallback($request->status),
        };
    }

    private static function requested(bool $justSubmitted): array
    {
        return [
            'accent' => 'primary',
            'heroIcon' => $justSubmitted ? 'check' : 'clock',
            'titleKey' => $justSubmitted ? 'recipient.request_submitted.title' : 'recipient.request_submitted.title_pending',
            'subtitleKey' => $justSubmitted ? 'recipient.request_submitted.subtitle' : 'recipient.request_submitted.subtitle_pending',
            'badgeClass' => 'bg-warning/10 text-warning dark:bg-warning/15',
            'badgeLabelKey' => 'recipient.request_progress.badge_requested',
            'steps' => [
                ['state' => 'done', 'hintKey' => $justSubmitted ? 'recipient.request_progress.hint_just_now' : 'recipient.request_progress.hint_received'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_awaiting'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_outcome'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending'],
            ],
            'footer' => 'requested',
        ];
    }

    private static function redeemReady(RequestModel $request): array
    {
        $badgeKey = $request->status === 'APPROVED'
            ? 'recipient.request_progress.badge_approved'
            : 'recipient.request_progress.badge_redeemable';

        return [
            'accent' => 'success',
            'heroIcon' => 'check',
            'titleKey' => 'recipient.request_redeem.title',
            'subtitleKey' => 'recipient.request_redeem.subtitle',
            'badgeClass' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
            'badgeLabelKey' => $badgeKey,
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step2_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step3_hint'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_redeem.step4_pending_hint'],
            ],
            'footer' => 'redeem',
        ];
    }

    private static function fulfilled(): array
    {
        return [
            'accent' => 'success',
            'heroIcon' => 'check',
            'titleKey' => 'recipient.request_fulfilled.title',
            'subtitleKey' => 'recipient.request_fulfilled.subtitle',
            'badgeClass' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
            'badgeLabelKey' => 'recipient.request_progress.badge_fulfilled',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step2_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step3_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_fulfilled.step4_hint'],
            ],
            'footer' => 'fulfilled',
        ];
    }

    private static function rejected(): array
    {
        return [
            'accent' => 'error',
            'heroIcon' => 'x',
            'titleKey' => 'recipient.request_progress.title_rejected',
            'subtitleKey' => 'recipient.request_progress.subtitle_rejected',
            'badgeClass' => 'bg-error/10 text-error dark:bg-error/15',
            'badgeLabelKey' => 'recipient.request_progress.badge_rejected',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint'],
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step2_hint'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_declined'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending'],
            ],
            'footer' => 'simple',
        ];
    }

    private static function adminRejected(): array
    {
        return [
            'accent' => 'error',
            'heroIcon' => 'x',
            'titleKey' => 'recipient.request_progress.title_admin_rejected',
            'subtitleKey' => 'recipient.request_progress.subtitle_admin_rejected',
            'badgeClass' => 'bg-error/10 text-error dark:bg-error/15',
            'badgeLabelKey' => 'recipient.request_progress.badge_admin_rejected',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint', 'labelKey' => 'recipient.request_progress.step1'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_admin_rejected', 'labelKey' => 'recipient.request_progress.admin_review'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_provider_review', 'labelKey' => 'recipient.request_progress.step2'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_redeem.step3_hint', 'labelKey' => 'recipient.request_progress.step3'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending', 'labelKey' => 'recipient.request_progress.step4'],
            ],
            'footer' => 'simple',
        ];
    }

    private static function cancelled(): array
    {
        return [
            'accent' => 'slate',
            'heroIcon' => 'ban',
            'titleKey' => 'recipient.request_progress.title_cancelled',
            'subtitleKey' => 'recipient.request_progress.subtitle_cancelled',
            'badgeClass' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200',
            'badgeLabelKey' => 'recipient.request_progress.badge_cancelled',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_cancelled_step'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_outcome'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending'],
            ],
            'footer' => 'simple',
        ];
    }

    private static function adminPending(): array
    {
        return [
            'accent' => 'warning',
            'heroIcon' => 'clock',
            'titleKey' => 'recipient.request_progress.title_admin_pending',
            'subtitleKey' => 'recipient.request_progress.subtitle_admin_pending',
            'badgeClass' => 'bg-warning/10 text-warning dark:bg-warning/15',
            'badgeLabelKey' => 'recipient.request_progress.badge_admin_pending',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint', 'labelKey' => 'recipient.request_progress.step1'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_admin_review_pending', 'labelKey' => 'recipient.request_progress.admin_review'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_provider_review', 'labelKey' => 'recipient.request_progress.step2'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_redeem.step3_hint', 'labelKey' => 'recipient.request_progress.step3'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending', 'labelKey' => 'recipient.request_progress.step4'],
            ],
            'footer' => 'simple',
        ];
    }

    private static function adminApproved(): array
    {
        return [
            'accent' => 'success',
            'heroIcon' => 'check',
            'titleKey' => 'recipient.request_progress.title_admin_approved',
            'subtitleKey' => 'recipient.request_progress.subtitle_admin_approved',
            'badgeClass' => 'bg-success/10 text-success dark:bg-success/15',
            'badgeLabelKey' => 'recipient.request_progress.badge_admin_approved',
            'steps' => [
                ['state' => 'done', 'hintKey' => 'recipient.request_redeem.step1_hint', 'labelKey' => 'recipient.request_progress.step1'],
                ['state' => 'done', 'hintKey' => 'recipient.request_progress.hint_admin_review_done', 'labelKey' => 'recipient.request_progress.admin_review'],
                ['state' => 'current', 'hintKey' => 'recipient.request_progress.hint_provider_review', 'labelKey' => 'recipient.request_progress.step2'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_redeem.step3_hint', 'labelKey' => 'recipient.request_progress.step3'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending', 'labelKey' => 'recipient.request_progress.step4'],
            ],
            'footer' => 'simple',
        ];
    }

    private static function fallback(string $status): array
    {
        return [
            'accent' => 'slate',
            'heroIcon' => 'clock',
            'titleKey' => 'recipient.request_progress.title_generic',
            'subtitleKey' => 'recipient.request_progress.subtitle_generic',
            'badgeClass' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200',
            'badgeLabelKey' => null,
            'badgeStatusRaw' => str_replace('_', ' ', $status),
            'steps' => [
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_outcome'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_outcome'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_outcome'],
                ['state' => 'pending', 'hintKey' => 'recipient.request_progress.hint_fulfilled_pending'],
            ],
            'footer' => 'simple',
        ];
    }
}
