@extends('layouts.master')

@section('title', __('Claim First Parties List'))

@section('content')
    <div class="pagetitle">
      <h1>{{ __('Claim First Parties List') }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">{{ __('Settings') }}</li>
          <li class="breadcrumb-item active">{{ __('Claim First Parties') }}</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <div class="card d-inline-block">
        <div class="card-body p-20">
            <a href="{{ route('claim_first_parties.create') }}" class="btn btn-success">{{ __('Add New Claim First Party') }}</a>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-20">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col" class="col-1">{{ __('#') }}</th>
                    <th scope="col" class="col-9">{{ __('Name') }}</th>
                    <th scope="col" class="col-2">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                  @forelse($claimFirstParties as $claimFirstParty)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td class="text-start">{{ $claimFirstParty->name }}</td>
                            <td>
                                <a href="{{ route('claim_first_parties.edit', $claimFirstParty->id) }}" class="btn btn-primary btn-sm me-1">{{ __('Edit') }}</a>

                                @include('lookups::components.delete-button', [
                                    'action' => route('claim_first_parties.destroy', $claimFirstParty->id),
                                    'confirm' => __('Are you sure to delete this claim first party?'),
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ __('No claim first parties found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
              </table>
            </div>
        </div>
    </div>
@endsection
