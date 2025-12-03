@extends('frontend.layouts.app')

@section('title', 'Submit Complaint')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Submit Complaint</h3>
                    <a href="{{ route('customer.complaints.index') }}" class="btn btn-outline-secondary btn-sm">
                        Back to Complaints
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('customer.complaints.store') }}" method="POST"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small mb-1">Title</label>
                                <input type="text"
                                       name="title"
                                       value="{{ old('title') }}"
                                       class="form-control form-control-sm @error('title') is-invalid @enderror"
                                       placeholder="Brief summary of your complaint">
                                @error('title')
                                <div class="invalid-feedback small">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small mb-1">Message</label>
                                <textarea
                                    name="message"
                                    rows="4"
                                    class="form-control form-control-sm @error('message') is-invalid @enderror"
                                    placeholder="Describe the issue in detail">{{ old('message') }}</textarea>
                                @error('message')
                                <div class="invalid-feedback small">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small mb-1">Attachment (optional)</label>
                                <input type="file"
                                       name="attachment"
                                       class="form-control form-control-sm @error('attachment') is-invalid @enderror">
                                <div class="form-text">Maximum size 2MB. You can attach a screenshot or document if needed.</div>
                                @error('attachment')
                                <div class="invalid-feedback small">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('customer.complaints.index') }}"
                                   class="btn btn-outline-secondary btn-sm">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Submit Complaint
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

