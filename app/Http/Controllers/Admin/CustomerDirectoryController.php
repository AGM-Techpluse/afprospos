<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Every customer, registered or walk-in-only — BRD-adjacent stakeholder ask: a place to see who's been recorded during sales/repairs without a self-service account. */
final class CustomerDirectoryController
{
    public function __construct(private readonly CustomerDirectoryQuery $customers) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $registration = $request->string('registration')->toString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $this->customers->paginate(
                search: $search ?: null,
                registration: $registration ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $search,
                'registration' => $registration,
            ],
        ]);
    }
}
