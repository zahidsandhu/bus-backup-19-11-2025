@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto py-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-semibold">My Complaints</h1>
            <a href="{{ route('customer.complaints.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                New Complaint
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-4 py-2"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse ($complaints as $complaint)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium text-gray-900">{{ $complaint->title }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs
                                @if($complaint->status === 'open') bg-yellow-100 text-yellow-800
                                @elseif($complaint->status === 'in-progress') bg-blue-100 text-blue-800
                                @elseif($complaint->status === 'resolved') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst(str_replace('-', ' ', $complaint->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ $complaint->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('customer.complaints.show', $complaint) }}"
                               class="text-blue-600 hover:underline text-sm">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            You have not submitted any complaints yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $complaints->links() }}
        </div>
    </div>
@endsection


