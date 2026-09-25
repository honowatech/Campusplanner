<!DOCTYPE html>
<html class="loading" lang="{{ env('APP_LOCALE') }}" data-textdirection="ltr">
    <!-- BEGIN: Head-->

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui"
        />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ env("APP_NAME") }}</title>

        {{--  <link rel="stylesheet" href="{{asset('modules/planning/css/calendar/style.css')}}"> --}}
        <script src="{{ asset('modules/planning/js/jquery-3.7.1.min.js') }}"></script>

        <link
        rel="shortcut icon"
            type="image/x-icon"
            href="{{ asset('app-assets/images/ico/favicon.ico') }}"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
            rel="stylesheet"
        />

        <!-- BEGIN: Vendor CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/pages/app-invoice.css') }}">
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/vendors/css/vendors.min.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/vendors/css/charts/apexcharts.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/extensions/toastr.min.css')
            }}"
        />
        <!-- BEGIN: Vendor CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/forms/select/select2.min.css')
            }}"
        />
        <!-- END: Vendor CSS-->
        <link
            href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
            rel="stylesheet }}"
        />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <!-- END: Vendor CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/vendors/css/vendors.min.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/forms/spinner/jquery.bootstrap-touchspin.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/extensions/swiper.min.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/extensions/toastr.min.css')
            }}"
        />
        <link
        rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/css/core/menu/menu-types/horizontal-menu.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/pages/app-ecommerce-details.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/css/plugins/forms/form-number-input.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/css/plugins/extensions/ext-component-toastr.css'
                )
            }}"
        />
        <!-- BEGIN: Theme CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/bootstrap.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/bootstrap-extended.css') }}"
        />
        <link
        rel="stylesheet"
        type="text/css"
            href="{{ asset('app-assets/css/colors.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/components.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/themes/dark-layout.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/themes/bordered-layout.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/css/core/menu/menu-types/vertical-menu.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/css/plugins/forms/pickers/form-flat-pickr.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/css/plugins/forms/form-validation.css')
                }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/pages/app-user.css') }}"
        />
        <!-- BEGIN: Vendor CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/tables/datatable/dataTables.bootstrap4.min.css '
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/tables/datatable/responsive.bootstrap4.min.css '
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/tables/datatable/buttons.bootstrap4.min.css '
                )
            }}"
        />
        <link
        rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/tables/datatable/rowGroup.bootstrap4.min.css '
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css '
                )
            }}"
        />
        <!-- END: Vendor CSS-->
        <!-- BEGIN: Theme CSS-->
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('assets/css/style.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/bootstrap.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/bootstrap-extended.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/colors.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/components.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/themes/dark-layout.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/themes/bordered-layout.css ') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/pickers/pickadate/pickadate.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/css/plugins/forms/pickers/form-flat-pickr.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/css/plugins/forms/pickers/form-pickadate.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/pickers/pickadate/pickadate.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset(
                    'app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css'
                )
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/vendors/css/animate/animate.min.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/extensions/sweetalert2.min.css')
            }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{ asset('app-assets/css/plugins/forms/form-wizard.css') }}"
        />
        <link
            rel="stylesheet"
            type="text/css"
            href="{{
                asset('app-assets/vendors/css/forms/wizard/bs-stepper.min.css')
            }}"
        />
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/pages/app-email.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/plugins/extensions/ext-component-toastr.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/pages/app-email.css') }}">


        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/katex.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/monokai-sublime.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/quill.snow.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/quill.bubble.css') }}">
        <link rel="preconnect" href="https://fonts.gstatic.com">
        <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Inconsolata&amp;family=Roboto+Slab&amp;family=Slabo+27px&amp;family=Sofia&amp;family=Ubuntu+Mono&amp;display=swap">


        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/plugins/forms/form-quill-editor.css') }}">
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.0/dist/trix.css">
        <!-- BEGIN: Custom CSS-->
        <!-- END: Custom CSS-->
    </head>
    <!-- END: Head-->

    <!-- BEGIN: Body-->

    <body
        class="vertical-layout vertical-menu-modern navbar-floating footer-static"
        data-open="click"
        data-menu="vertical-menu-modern"
        data-col=""
    >

        <x-planning::topbar />

        @include(env('ASIDE_MENU'))

        <!-- BEGIN: Content-->
        <div class="app-content content @if ($message ?? null) email-application @endif">
            @session("success")
            <div class="row col-12">
                <div class="col-12 col-md-8">
                    <div class="alert alert-success p-2" role="alert">
                        <div class="d-flex gap-4">
                            <span class="mr-1"><i class="fa-solid fa-circle-check icon-primary"></i></span>
                            <div class="d-flex flex-column gap-2">
                            <h6 class="mb-0">{{ __('crm::components.common.success') }} !</h6>
                            <p class="mb-0">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endsession
            @if ($errors->any())
            <div class="row col-12">
                <div class="col-12 col-md-8">
                    <div class="alert alert-danger p-2" role="alert">
                        <div class="d-flex gap-4">
                            <span class="mr-1"><i class="fa-solid fa-circle-check icon-primary"></i></span>
                            <div class="d-flex flex-column gap-2">
                                <h6 class="mb-0">{{ __('crm::components.common.error') }} !</h6>
                                <p class="mb-0">{{ session('error') ?? __('crm::components.common.error_message') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div class="content-overlay"></div>
            <div class="header-navbar-shadow"></div>
            {{ $slot }}
        </div>
        <!-- END: Content-->

        <div class="sidenav-overlay"></div>
        <div class="drag-target"></div>

        {{ $footer }}

        <!-- BEGIN: Vendor JS-->
        <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
        <!-- BEGIN Vendor JS-->

        <!-- BEGIN: Page Vendor JS-->
        <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
        <!-- END: Page Vendor JS-->

        <!-- BEGIN: Theme JS-->
        <script src="{{ asset('app-assets/js/core/app-menu.js') }}"></script>
        <script src="{{ asset('app-assets/js/core/app.js') }}"></script>
        <!-- END: Theme JS-->

        <script src="{{ asset('app-assets/js/scripts/forms/form-select2.js') }}"></script>
        <script src="{{ asset('app-assets/js/scripts/pages/app-invoice.js') }}"></script>
        <script src="{{ asset('modules/crm/js/script.js') }}"></script>
        <!-- BEGIN: Page JS-->
        <!-- END: Page JS-->
        <script>
            var domaineName = @json(env('APP_URL'));
        </script>

        <script>
            $(window).on("load", function () {
                if (feather) {
                    feather.replace({
                        width: 14,
                        height: 14,
                    });
                }
            });
        </script>
    </body>
    <!-- END: Body-->
</html>
