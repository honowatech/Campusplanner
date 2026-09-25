<!-- BEGIN: Header-->
<nav
    class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow"
>
    <div class="navbar-container d-flex content">
        <div class="bookmark-wrapper d-flex align-items-center">
            <ul class="nav navbar-nav d-xl-none">
                <li class="nav-item">
                    <a class="nav-link menu-toggle" href="javascript:void(0);"
                        ><i class="ficon" data-feather="menu"></i
                    ></a>
                </li>
            </ul>
            <ul class="nav navbar-nav bookmark-icons">
                <li class="nav-item d-none d-lg-block">
                    <a
                        class="nav-link"
                        data-placement="top"
                        data-toggle="tooltip"
                        href="app-email.html"
                        title="Email"
                        ><i class="ficon" data-feather="mail"></i
                    ></a>
                </li>
                <li class="nav-item d-none d-lg-block">
                    <a
                        class="nav-link"
                        data-placement="top"
                        data-toggle="tooltip"
                        href="app-chat.html"
                        title="Chat"
                        ><i class="ficon" data-feather="message-square"></i
                    ></a>
                </li>
                <li class="nav-item d-none d-lg-block">
                    <a
                        class="nav-link"
                        data-placement="top"
                        data-toggle="tooltip"
                        href="app-calendar.html"
                        title="Calendar"
                        ><i class="ficon" data-feather="calendar"></i
                    ></a>
                </li>
                <li class="nav-item d-none d-lg-block">
                    <a
                        class="nav-link"
                        data-placement="top"
                        data-toggle="tooltip"
                        href="app-todo.html"
                        title="Todo"
                        ><i class="ficon" data-feather="check-square"></i
                    ></a>
                </li>
            </ul>
            <ul class="nav navbar-nav">
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link bookmark-star"
                        ><i class="ficon text-warning" data-feather="star"></i
                    ></a>
                    <div class="bookmark-input search-input">
                        <div class="bookmark-input-icon">
                            <i data-feather="search"></i>
                        </div>
                        <input
                            class="form-control input"
                            data-search="search"
                            placeholder="Bookmark"
                            tabindex="0"
                            type="text"
                        />
                        <ul class="search-list search-list-bookmark"></ul>
                    </div>
                </li>
            </ul>
        </div>
        <ul class="nav navbar-nav align-items-center ml-auto">
            {{-- <li class="nav-item dropdown dropdown-language">
                <a
                    aria-expanded="false"
                    aria-haspopup="true"
                    class="nav-link dropdown-toggle"
                    data-toggle="dropdown"
                    href="javascript:void(0);"
                    id="dropdown-flag"
                    ><i class="flag-icon flag-icon-{{ app()->getLocale() == 'fr' ? 'fr' : 'us' }}"></i
                    ><span class="selected-language">{{ __('components.common.select') }}</span></a
                >
                <div
                    aria-labelledby="dropdown-flag"
                    class="dropdown-menu dropdown-menu-right"
                >
                    @if(app()->getLocale() != 'en')
                    <a
                        class="dropdown-item"
                        href="{{ route('language.switch', 'en') }}"
                        data-language="en"
                        ><i class="flag-icon flag-icon-us"></i> English</a
                    >
                    @endif
                    @if(app()->getLocale() != 'fr')
                    <a
                        class="dropdown-item"
                        href="{{ route('language.switch', 'fr') }}"
                        data-language="fr"
                        ><i class="flag-icon flag-icon-fr"></i> Français</a
                    >
                    @endif
                </div>
            </li> --}}
            <li class="nav-item d-none d-lg-block">
                <a class="nav-link nav-link-style"
                    ><i class="ficon" data-feather="moon"></i
                ></a>
            </li>
            <li class="nav-item nav-search">
                <a class="nav-link nav-link-search"
                    ><i class="ficon" data-feather="search"></i
                ></a>
                <div class="search-input">
                    <div class="search-input-icon">
                        <i data-feather="search"></i>
                    </div>
                    <input
                        class="form-control input"
                        data-search="search"
                        placeholder="Explore Vuexy..."
                        tabindex="-1"
                        type="text"
                    />
                    <div class="search-input-close">
                        <i data-feather="x"></i>
                    </div>
                    <ul class="search-list search-list-main"></ul>
                </div>
            </li>
            <li class="nav-item dropdown dropdown-cart mr-25">
                <a
                    class="nav-link"
                    data-toggle="dropdown"
                    href="javascript:void(0);"
                    ><i class="ficon" data-feather="shopping-cart"></i
                    ><span
                        class="badge badge-pill badge-primary badge-up cart-item-count"
                        >6</span
                    ></a
                >
                <ul
                    class="dropdown-menu dropdown-menu-media dropdown-menu-right"
                >
                    <li class="dropdown-menu-header">
                        <div class="dropdown-header d-flex">
                            <h4 class="notification-title mb-0 mr-auto">
                                My Cart
                            </h4>
                            <div class="badge badge-pill badge-light-primary">
                                4 Items
                            </div>
                        </div>
                    </li>
                    <li class="scrollable-container media-list">
                        <div class="media align-items-center">
                            <img
                                alt="donuts"
                                class="d-block mr-1 rounded"
                                src="../../../app-assets/images/pages/eCommerce/1.png"
                                width="62"
                            />
                            <div class="media-body">
                                <i
                                    class="ficon cart-item-remove"
                                    data-feather="x"
                                ></i>
                                <div class="media-heading">
                                    <h6 class="cart-item-title">
                                        <a
                                            class="text-body"
                                            href="app-ecommerce-details.html"
                                        >
                                            Apple watch 5</a
                                        >
                                    </h6>
                                    <small class="cart-item-by">By Apple</small>
                                </div>
                                <div class="cart-item-qty">
                                    <div class="input-group">
                                        <input
                                            class="touchspin-cart"
                                            type="number"
                                            value="1"
                                        />
                                    </div>
                                </div>
                                <h5 class="cart-item-price">$374.90</h5>
                            </div>
                        </div>
                        <div class="media align-items-center">
                            <img
                                alt="donuts"
                                class="d-block mr-1 rounded"
                                src="../../../app-assets/images/pages/eCommerce/7.png"
                                width="62"
                            />
                            <div class="media-body">
                                <i
                                    class="ficon cart-item-remove"
                                    data-feather="x"
                                ></i>
                                <div class="media-heading">
                                    <h6 class="cart-item-title">
                                        <a
                                            class="text-body"
                                            href="app-ecommerce-details.html"
                                        >
                                            Google Home Mini</a
                                        >
                                    </h6>
                                    <small class="cart-item-by"
                                        >By Google</small
                                    >
                                </div>
                                <div class="cart-item-qty">
                                    <div class="input-group">
                                        <input
                                            class="touchspin-cart"
                                            type="number"
                                            value="3"
                                        />
                                    </div>
                                </div>
                                <h5 class="cart-item-price">$129.40</h5>
                            </div>
                        </div>
                        <div class="media align-items-center">
                            <img
                                alt="donuts"
                                class="d-block mr-1 rounded"
                                src="../../../app-assets/images/pages/eCommerce/2.png"
                                width="62"
                            />
                            <div class="media-body">
                                <i
                                    class="ficon cart-item-remove"
                                    data-feather="x"
                                ></i>
                                <div class="media-heading">
                                    <h6 class="cart-item-title">
                                        <a
                                            class="text-body"
                                            href="app-ecommerce-details.html"
                                        >
                                            iPhone 11 Pro</a
                                        >
                                    </h6>
                                    <small class="cart-item-by">By Apple</small>
                                </div>
                                <div class="cart-item-qty">
                                    <div class="input-group">
                                        <input
                                            class="touchspin-cart"
                                            type="number"
                                            value="2"
                                        />
                                    </div>
                                </div>
                                <h5 class="cart-item-price">$699.00</h5>
                            </div>
                        </div>
                        <div class="media align-items-center">
                            <img
                                alt="donuts"
                                class="d-block mr-1 rounded"
                                src="../../../app-assets/images/pages/eCommerce/3.png"
                                width="62"
                            />
                            <div class="media-body">
                                <i
                                    class="ficon cart-item-remove"
                                    data-feather="x"
                                ></i>
                                <div class="media-heading">
                                    <h6 class="cart-item-title">
                                        <a
                                            class="text-body"
                                            href="app-ecommerce-details.html"
                                        >
                                            iMac Pro</a
                                        >
                                    </h6>
                                    <small class="cart-item-by">By Apple</small>
                                </div>
                                <div class="cart-item-qty">
                                    <div class="input-group">
                                        <input
                                            class="touchspin-cart"
                                            type="number"
                                            value="1"
                                        />
                                    </div>
                                </div>
                                <h5 class="cart-item-price">$4,999.00</h5>
                            </div>
                        </div>
                        <div class="media align-items-center">
                            <img
                                alt="donuts"
                                class="d-block mr-1 rounded"
                                src="../../../app-assets/images/pages/eCommerce/5.png"
                                width="62"
                            />
                            <div class="media-body">
                                <i
                                    class="ficon cart-item-remove"
                                    data-feather="x"
                                ></i>
                                <div class="media-heading">
                                    <h6 class="cart-item-title">
                                        <a
                                            class="text-body"
                                            href="app-ecommerce-details.html"
                                        >
                                            MacBook Pro</a
                                        >
                                    </h6>
                                    <small class="cart-item-by">By Apple</small>
                                </div>
                                <div class="cart-item-qty">
                                    <div class="input-group">
                                        <input
                                            class="touchspin-cart"
                                            type="number"
                                            value="1"
                                        />
                                    </div>
                                </div>
                                <h5 class="cart-item-price">$2,999.00</h5>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-menu-footer">
                        <div class="d-flex justify-content-between mb-1">
                            <h6 class="font-weight-bolder mb-0">Total:</h6>
                            <h6 class="text-primary font-weight-bolder mb-0">
                                $10,999.00
                            </h6>
                        </div>
                        <a
                            class="btn btn-primary btn-block"
                            href="app-ecommerce-checkout.html"
                            >Checkout</a
                        >
                    </li>
                </ul>
            </li>
            <li class="nav-item dropdown dropdown-notification mr-25">
                <a
                    class="nav-link"
                    data-toggle="dropdown"
                    href="javascript:void(0);"
                    ><i class="ficon" data-feather="bell"></i
                    ><span class="badge badge-pill badge-danger badge-up"
                        >5</span
                    ></a
                >
                <ul
                    class="dropdown-menu dropdown-menu-media dropdown-menu-right"
                >
                    <li class="dropdown-menu-header">
                        <div class="dropdown-header d-flex">
                            <h4 class="notification-title mb-0 mr-auto">
                                Notifications
                            </h4>
                            <div class="badge badge-pill badge-light-primary">
                                6 New
                            </div>
                        </div>
                    </li>
                    <li class="scrollable-container media-list">
                        <a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar">
                                        <img
                                            alt="avatar"
                                            height="32"
                                            src="../../../app-assets/images/portrait/small/avatar-s-15.jpg"
                                            width="32"
                                        />
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >Congratulation Sam 🎉</span
                                        >winner!
                                    </p>
                                    <small class="notification-text">
                                        Won the monthly best seller
                                        badge.</small
                                    >
                                </div>
                            </div> </a
                        ><a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar">
                                        <img
                                            alt="avatar"
                                            height="32"
                                            src="../../../app-assets/images/portrait/small/avatar-s-3.jpg"
                                            width="32"
                                        />
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >New message</span
                                        >&nbsp;received
                                    </p>
                                    <small class="notification-text">
                                        You have 10 unread messages</small
                                    >
                                </div>
                            </div> </a
                        ><a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar bg-light-danger">
                                        <div class="avatar-content">MD</div>
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >Revised Order 👋</span
                                        >&nbsp;checkout
                                    </p>
                                    <small class="notification-text">
                                        MD Inc. order updated</small
                                    >
                                </div>
                            </div>
                        </a>
                        <div class="media d-flex align-items-center">
                            <h6 class="font-weight-bolder mb-0 mr-auto">
                                System Notifications
                            </h6>
                            <div
                                class="custom-control custom-control-primary custom-switch"
                            >
                                <input
                                    checked=""
                                    class="custom-control-input"
                                    id="systemNotification"
                                    type="checkbox"
                                />
                                <label
                                    class="custom-control-label"
                                    for="systemNotification"
                                ></label>
                            </div>
                        </div>
                        <a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar bg-light-danger">
                                        <div class="avatar-content">
                                            <i
                                                class="avatar-icon"
                                                data-feather="x"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >Server down</span
                                        >&nbsp;registered
                                    </p>
                                    <small class="notification-text">
                                        USA Server is down due to hight CPU
                                        usage</small
                                    >
                                </div>
                            </div> </a
                        ><a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar bg-light-success">
                                        <div class="avatar-content">
                                            <i
                                                class="avatar-icon"
                                                data-feather="check"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >Sales report</span
                                        >&nbsp;generated
                                    </p>
                                    <small class="notification-text">
                                        Last month sales report generated</small
                                    >
                                </div>
                            </div> </a
                        ><a class="d-flex" href="javascript:void(0)">
                            <div class="media d-flex align-items-start">
                                <div class="media-left">
                                    <div class="avatar bg-light-warning">
                                        <div class="avatar-content">
                                            <i
                                                class="avatar-icon"
                                                data-feather="alert-triangle"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-body">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder"
                                            >High memory</span
                                        >&nbsp;usage
                                    </p>
                                    <small class="notification-text">
                                        BLR Server using high memory</small
                                    >
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="dropdown-menu-footer">
                        <a
                            class="btn btn-primary btn-block"
                            href="javascript:void(0)"
                            >Read all notifications</a
                        >
                    </li>
                </ul>
            </li>
            <li class="nav-item dropdown dropdown-user">
                <a
                    aria-expanded="false"
                    aria-haspopup="true"
                    class="nav-link dropdown-toggle dropdown-user-link"
                    data-toggle="dropdown"
                    href="javascript:void(0);"
                    id="dropdown-user"
                >
                    <div class="user-nav d-sm-flex d-none">
                        <span class="user-name font-weight-bolder"
                            >John Doe</span
                        ><span class="user-status">Admin</span>
                    </div>
                    <span class="avatar"
                        ><img
                            alt="avatar"
                            class="round"
                            height="40"
                            src="../../../app-assets/images/portrait/small/avatar-s-11.jpg"
                            width="40" /><span
                            class="avatar-status-online"
                        ></span
                    ></span>
                </a>
                <div
                    aria-labelledby="dropdown-user"
                    class="dropdown-menu dropdown-menu-right"
                >
                    <a class="dropdown-item" href="page-profile.html"
                        ><i class="mr-50" data-feather="user"></i> Profile</a
                    ><a class="dropdown-item" href="app-email.html"
                        ><i class="mr-50" data-feather="mail"></i> Inbox</a
                    ><a class="dropdown-item" href="app-todo.html"
                        ><i class="mr-50" data-feather="check-square"></i>
                        Task</a
                    ><a class="dropdown-item" href="app-chat.html"
                        ><i class="mr-50" data-feather="message-square"></i>
                        Chats</a
                    >
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="page-account-settings.html"
                        ><i class="mr-50" data-feather="settings"></i>
                        Settings</a
                    ><a class="dropdown-item" href="page-pricing.html"
                        ><i class="mr-50" data-feather="credit-card"></i>
                        Pricing</a
                    ><a class="dropdown-item" href="page-faq.html"
                        ><i class="mr-50" data-feather="help-circle"></i> FAQ</a
                    ><a class="dropdown-item" href="page-auth-login-v2.html"
                        ><i class="mr-50" data-feather="power"></i> Logout</a
                    >
                </div>
            </li>
        </ul>
    </div>
</nav>
<ul class="main-search-list-defaultlist d-none">
    <li class="d-flex align-items-center">
        <a href="javascript:void(0);">
            <h6 class="section-label mt-75 mb-0">Files</h6>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/icons/xls.png"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Two new item submitted</p>
                    <small class="text-muted">Marketing Manager</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;17kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/icons/jpg.png"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">52 JPG file Generated</p>
                    <small class="text-muted">FontEnd Developer</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;11kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/icons/pdf.png"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">25 PDF File Uploaded</p>
                    <small class="text-muted">Digital Marketing Manager</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;150kb</small>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html"
        >
            <div class="d-flex">
                <div class="mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/icons/doc.png"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Anna_Strong.doc</p>
                    <small class="text-muted">Web Designer</small>
                </div>
            </div>
            <small class="search-data-size mr-50 text-muted">&apos;256kb</small>
        </a>
    </li>
    <li class="d-flex align-items-center">
        <a href="javascript:void(0);">
            <h6 class="section-label mt-75 mb-0">Members</h6>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view.html"
        >
            <div class="d-flex align-items-center">
                <div class="avatar mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/portrait/small/avatar-s-8.jpg"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">John Doe</p>
                    <small class="text-muted">UI designer</small>
                </div>
            </div>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view.html"
        >
            <div class="d-flex align-items-center">
                <div class="avatar mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/portrait/small/avatar-s-1.jpg"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Michal Clark</p>
                    <small class="text-muted">FontEnd Developer</small>
                </div>
            </div>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view.html"
        >
            <div class="d-flex align-items-center">
                <div class="avatar mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/portrait/small/avatar-s-14.jpg"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Milena Gibson</p>
                    <small class="text-muted">Digital Marketing Manager</small>
                </div>
            </div>
        </a>
    </li>
    <li class="auto-suggestion">
        <a
            class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view.html"
        >
            <div class="d-flex align-items-center">
                <div class="avatar mr-75">
                    <img
                        alt="png"
                        height="32"
                        src="../../../app-assets/images/portrait/small/avatar-s-6.jpg"
                    />
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Anna Strong</p>
                    <small class="text-muted">Web Designer</small>
                </div>
            </div>
        </a>
    </li>
</ul>
<ul class="main-search-list-defaultlist-other-list d-none">
    <li class="auto-suggestion justify-content-between">
        <a
            class="d-flex align-items-center justify-content-between w-100 py-50"
        >
            <div class="d-flex justify-content-start">
                <span class="mr-75" data-feather="alert-circle"></span
                ><span>No results found.</span>
            </div>
        </a>
    </li>
</ul>
<!-- END: Header-->
