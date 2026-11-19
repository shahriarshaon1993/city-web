<?php

namespace App\Queries\Admin;

use App\Models\JobApplication;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use App\Support\Leads\LeadTypes;
use App\Support\Privacy\PersonalDataTerm;
use Illuminate\Database\Eloquent\Builder;

/**
 * NFR-PRIV-03: everything we hold for a phone and/or an email — leads (also those in the Recycle bin),
 * job applications and newsletter subscriptions. The same three queries serve the page, the export
 * and the erase, so what is shown is exactly what is exported and erased.
 */
final class PersonalDataLookup
{
    /**
     * @return Builder<Lead>
     */
    public function leads(PersonalDataTerm $term): Builder
    {
        return Lead::withTrashed()->where(function (Builder $query) use ($term): void {
            if ($term->phone !== null) {
                $query->orWhere('phone', $term->phone)->orWhere('whatsapp', $term->phone);
            }

            if ($term->email !== null) {
                $query->orWhere('email', $term->email);
            }
        });
    }

    /**
     * @return Builder<JobApplication>
     */
    public function applications(PersonalDataTerm $term): Builder
    {
        return JobApplication::withTrashed()->where(function (Builder $query) use ($term): void {
            if ($term->phone !== null) {
                $query->orWhere('phone', $term->phone);
            }

            if ($term->email !== null) {
                $query->orWhere('email', $term->email);
            }
        });
    }

    /**
     * Subscriptions carry an email only, so a phone alone finds none.
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function subscribers(PersonalDataTerm $term): Builder
    {
        $query = NewsletterSubscriber::withTrashed();

        return $term->email === null ? $query->whereRaw('1 = 0') : $query->where('email', $term->email);
    }

    /**
     * What the page lists: enough to recognise the records, no message texts.
     *
     * @return array{
     *     leads: list<array{id: int, name: string, phone: string, email: string|null, type: string, project: string|null, status: string, created_at: string|null, in_bin: bool}>,
     *     applications: list<array{id: int, name: string, phone: string, email: string, job: string, status: string, created_at: string|null, in_bin: bool}>,
     *     subscriptions: list<array{id: int, email: string, status: string, created_at: string|null, in_bin: bool}>,
     * }
     */
    public function summary(PersonalDataTerm $term): array
    {
        return [
            'leads' => array_values($this->leads($term)->with('project:id,name')->orderByDesc('id')->limit(200)->get()
                ->map(fn (Lead $lead): array => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'type' => LeadTypes::label($lead->type),
                    'project' => $lead->project?->name,
                    'status' => $lead->status->label(),
                    'created_at' => $lead->created_at?->toIso8601String(),
                    'in_bin' => $lead->trashed(),
                ])->all()),
            'applications' => array_values($this->applications($term)->with('job:id,title')->orderByDesc('id')->limit(200)->get()
                ->map(fn (JobApplication $application): array => [
                    'id' => $application->id,
                    'name' => $application->name,
                    'phone' => $application->phone,
                    'email' => $application->email,
                    'job' => $application->job->title ?? 'General CV',
                    'status' => $application->status->label(),
                    'created_at' => $application->created_at?->toIso8601String(),
                    'in_bin' => $application->trashed(),
                ])->all()),
            'subscriptions' => array_values($this->subscribers($term)->orderByDesc('id')->limit(200)->get()
                ->map(fn (NewsletterSubscriber $subscriber): array => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'status' => ucfirst($subscriber->status->value),
                    'created_at' => $subscriber->created_at?->toIso8601String(),
                    'in_bin' => $subscriber->trashed(),
                ])->all()),
        ];
    }
}
