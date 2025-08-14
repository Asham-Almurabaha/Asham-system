<?php
    $types = App\Models\admin\setting\company_type::All();
?>

<div class="modal fade" id="AddCompanyModal"> <!-- Start Add Form -->
    <div class="modal-dialog modal-lg">
        <form method="POST" id="AddCompanyForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add_error_list"></div>
                    <div class="row mb-3">
                        <label for="Type" class="col-md-4 col-lg-3 col-form-label">Type</label>
                        <div class="col-md-8 col-lg-9 ">
                            <select class="form-select" name="Type" id="Type">
                                <option id="0" value="" selected disabled></option>
                                @foreach ($types as $item)
                                    <option id="{{$item->id}}" value="{{$item->id}}"> {{$item->name}} </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="Name" class="col-md-4 col-lg-3 col-form-label">Name</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="Name" type="text" class="form-control" id="Name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="Registration_number" class="col-md-4 col-lg-3 col-form-label">Registration Number</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="Registration_number" type="text" class="form-control" id="Registration_number">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="Border_number" class="col-md-4 col-lg-3 col-form-label">Border Number</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="Border_number" type="text" class="form-control" id="Border_number">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="Registration_date" class="col-md-4 col-lg-3 col-form-label">Registration Date</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="Registration_date" type="date" class="form-control" id="Registration_date">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="Expiry_date" class="col-md-4 col-lg-3 col-form-label">Expiry Date</label>
                        <div class="col-md-8 col-lg-9">
                            <input name="Expiry_date" type="date" class="form-control" id="Expiry_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="AddCompany" name="AddCompany" type="submit" class="btn btn-primary">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

