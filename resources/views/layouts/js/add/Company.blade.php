<script>
    $(document).on('click', '#add_company', function() {
            $('#AddModal').modal('toggle');
            $('#AddCompanyModal').modal('toggle');
        });

    $(document).on('click', '#AddCompanyForm #AddCompany', function(e) {
        e.preventDefault();
        var data = new FormData($('#AddCompanyForm')[0]);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            type: "POST",
            url: "{{ route('companies.create') }}",
            data: data,
            dataType: "json",
            enctype: "multipart/form-data",
            cache: false,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status == false) {
                    $.each(response.errors, function(key, value) {
                        $('#' + key).addClass("is-invalid");
                        $('.' + key).append(
                            '<span class="invalid-feedback" role="alert">\
                                <strong>' + value + '</strong>\
                            </span>'
                        )
                    });
                } else {
                    $('#AddCompanyModal').modal('hide');
                    $('#AddCompanyForm')[0].reset();
                    $('#AddModal').modal('toggle');
                    $("#success_message").html("");
                    $("#success_message").addClass("alert alert-success");
                    $("#success_message").text(response.message);
                    $("#success_message").fadeTo(2000, 500).slideUp(500, function() {
                        $("#success_message").slideUp(500);
                    });
                    // location.reload();
                }
            }
        });
    });
</script>