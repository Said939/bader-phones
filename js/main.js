/**
 * ============================================================================
 * بدر للهواتف / Bader Phones - Main Frontend Engine (js/main.js)
 * Modern, Responsive, Modular E-Commerce Interactions
 * Features:
 *   - Dark / Light Mode Sync
 *   - Collapsible Sidebar Drawer (Robust Click & Touch Handlers)
 *   - Interactive Shopping Cart Drawer (Local Storage, Real-time Totals, Counter)
 *   - WhatsApp Checkout Compilation & Direct Link (#FF6B00 / 96897447122)
 *   - Real-Time Live Search Autocomplete with Device Thumbnails
 *   - Sleek 2-Second Floating Corner Toast Notifications
 * ============================================================================
 */

(function () {
    'use strict';

    // Retrieve runtime configuration passed from PHP
    const cfg = window.ShopConfig || {};
    const currentLang = cfg.lang || 'ar';
    const currencySymbol = cfg.currency || (currentLang === 'ar' ? 'ر.ع' : 'OMR');
    const whatsappPhone = cfg.whatsappPhone || '96897447122';
    const catalogProducts = cfg.products || [];
    const i18nJs = cfg.i18n || {
        toastAdded: "تمت الإضافة للسلة بنجاح!",
        cartEmpty: "سلتك فارغة حالياً",
        cartEmptySub: "تصفح أحدث الأجهزة والعروض الحصرية وأضف ما يعجبك!",
        noResults: "لم يتم العثور على أجهزة مطابقة للبحث",
        viewHint: "انقر للانتقال للمنتج",
        toastLoggedIn: "مرحباً بعودتك! تم تسجيل الدخول بنجاح.",
        toastRegistered: "تم إنشاء الحساب وتسجيل الدخول بنجاح! أهلاً بك.",
        toastLoggedOut: "تم تسجيل الخروج بنجاح. نراك قريباً!"
    };

    // ------------------------------------------------------------------------
    // 1. Device Category Icons Helper
    // ------------------------------------------------------------------------
    function getDeviceIcon(type) {
        switch (type) {
            case 'iphone':  return '📱';
            case 'samsung': return '📱';
            case 'xiaomi':  return '📱';
            case 'macbook': return '💻';
            case 'dell':    return '💻';
            case 'anker':   return '🔋';
            case 'sony':    return '📷';
            case 'dji':     return '🎥';
            default:        return '📱';
        }
    }

    // ------------------------------------------------------------------------
    // 2. Theme Management (Dark / Light Mode)
    // ------------------------------------------------------------------------
    function initTheme() {
        const savedTheme = localStorage.getItem('bader_theme') || 'dark';
        setTheme(savedTheme);
    }

    window.setTheme = function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('bader_theme', theme);

        const pillDark = document.getElementById('pillThemeDark');
        const pillLight = document.getElementById('pillThemeLight');
        if (pillDark && pillLight) {
            pillDark.classList.toggle('active', theme === 'dark');
            pillLight.classList.toggle('active', theme === 'light');
        }
    };

    // ------------------------------------------------------------------------
    // 3. Collapsible Sidebar Navigation
    // ------------------------------------------------------------------------
    window.toggleSidebar = function (open) {
        const sidebar = document.getElementById('sidebarDrawer');
        const overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !overlay) return;

        if (open) {
            // Close cart if open to prevent stacking conflicts
            const cartDrawer = document.getElementById('cartDrawer');
            const cartOverlay = document.getElementById('cartOverlay');
            if (cartDrawer && cartOverlay) {
                cartDrawer.classList.remove('active');
                cartOverlay.classList.remove('active');
            }

            overlay.classList.add('active');
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            overlay.classList.remove('active');
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // ------------------------------------------------------------------------
    // 4. Cart Storage, State & Drawer Operations
    // ------------------------------------------------------------------------
    let cart = [];
    try {
        const savedCart = localStorage.getItem('bader_cart');
        if (savedCart) {
            const parsed = JSON.parse(savedCart);
            if (Array.isArray(parsed)) {
                cart = parsed;
            }
        }
    } catch (e) {
        cart = [];
    }

    function saveCart() {
        try {
            localStorage.setItem('bader_cart', JSON.stringify(cart));
        } catch (e) {}
        updateCartUI();
    }

    window.addToCart = function (slug, name, price, imgType) {
        if (!Array.isArray(cart)) cart = [];
        const existing = cart.find(item => item.slug === slug);
        if (existing) {
            existing.qty = (Number(existing.qty) || 1) + 1;
        } else {
            cart.push({
                slug: slug,
                name: name,
                price: parseFloat(price) || 0,
                qty: 1,
                imgType: imgType || 'iphone'
            });
        }
        saveCart();
        showCornerToast('🛍️', i18nJs.toastAdded, `${name} (${(parseFloat(price) || 0).toFixed(3)} ${currencySymbol})`);
    };

    window.changeCartQty = function (slug, delta) {
        if (!Array.isArray(cart)) return;
        const item = cart.find(i => i.slug === slug);
        if (!item) return;

        item.qty = (Number(item.qty) || 1) + delta;
        if (item.qty <= 0) {
            cart = cart.filter(i => i.slug !== slug);
        }
        saveCart();
    };

    window.removeFromCart = function (slug) {
        if (!Array.isArray(cart)) return;
        cart = cart.filter(i => i.slug !== slug);
        saveCart();
    };

    window.clearCart = function () {
        if (!Array.isArray(cart) || cart.length === 0) return;
        cart = [];
        saveCart();
    };

    function updateCartUI() {
        if (!Array.isArray(cart)) cart = [];

        const badge = document.getElementById('headerCartCount');
        const sideBadge = document.getElementById('sidebarCartCount');
        const container = document.getElementById('cartItemsContainer');
        const totalEl = document.getElementById('cartTotalPrice');
        const footerEl = document.getElementById('cartDrawerFooter');

        const totalCount = cart.reduce((sum, item) => sum + (Number(item.qty) || 1), 0);
        const totalPrice = cart.reduce((sum, item) => sum + ((Number(item.price) || 0) * (Number(item.qty) || 1)), 0);

        if (badge) {
            badge.textContent = totalCount;
            badge.style.transform = 'scale(1.35)';
            setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
        }

        if (sideBadge) sideBadge.textContent = totalCount;
        if (totalEl) totalEl.textContent = `${totalPrice.toFixed(3)} ${currencySymbol}`;

        if (!container) return;

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="cart-empty-state">
                    <div class="cart-empty-icon">🛒</div>
                    <h4 style="color:var(--text-primary); margin-bottom: 0.35rem; font-weight:700;">${i18nJs.cartEmpty}</h4>
                    <p style="font-size: 0.85rem; color:var(--text-muted);">${i18nJs.cartEmptySub}</p>
                </div>
            `;
            if (footerEl) footerEl.style.display = 'none';
            return;
        }

        if (footerEl) footerEl.style.display = 'block';

        let html = '';
        cart.forEach(item => {
            const price = Number(item.price) || 0;
            const qty = Number(item.qty) || 1;
            const subtotal = (price * qty).toFixed(3);
            const safeName = (item.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            html += `
                <div class="cart-item-card">
                    <div class="cart-item-thumb">${getDeviceIcon(item.imgType)}</div>
                    <div class="cart-item-details">
                        <div class="cart-item-name" title="${safeName}">${safeName}</div>
                        <div class="cart-item-price">${subtotal} ${currencySymbol}</div>
                    </div>
                    <div class="cart-qty-ctrl">
                        <button type="button" class="qty-btn" onclick="changeCartQty('${item.slug}', -1)" aria-label="Decrease quantity">-</button>
                        <span class="qty-val">${qty}</span>
                        <button type="button" class="qty-btn" onclick="changeCartQty('${item.slug}', 1)" aria-label="Increase quantity">+</button>
                    </div>
                    <button type="button" class="cart-item-delete" onclick="removeFromCart('${item.slug}')" title="Delete" aria-label="Remove item">🗑️</button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    window.toggleCartDrawer = function (open) {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        if (!drawer || !overlay) return;

        if (open) {
            // Close sidebar if open to prevent stacking conflicts
            const sidebar = document.getElementById('sidebarDrawer');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebar && sidebarOverlay) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }

            try {
                updateCartUI();
            } catch (err) {
                console.error("Cart UI update error:", err);
            }

            overlay.classList.add('active');
            drawer.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            overlay.classList.remove('active');
            drawer.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // ------------------------------------------------------------------------
    // 5. WhatsApp Direct Compilation Checkout (Vibrant Tech-Orange Buy Now)
    // ------------------------------------------------------------------------
    window.checkoutCartViaWhatsApp = function () {
        if (!Array.isArray(cart) || cart.length === 0) {
            showCornerToast('⚠️', cfg.shopName || "بدر للهواتف", i18nJs.cartEmpty);
            return;
        }

        let total = 0;
        const itemsText = cart.map((item, idx) => {
            const price = Number(item.price) || 0;
            const qty = Number(item.qty) || 1;
            const lineTotal = price * qty;
            total += lineTotal;
            return `${idx + 1}. ${item.name} × ${qty} (${lineTotal.toFixed(3)} ${currencySymbol})`;
        }).join('\n');

        let msg = '';
        if (currentLang === 'ar') {
            msg = `📱 *طلب شراء جديد من متجر بدر للهواتف*\n` +
                  `السلام عليكم، أود تأكيد وشراء المنتجات التالية من السلة:\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `${itemsText}\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `💰 *المجموع الإجمالي:* ${total.toFixed(3)} ${currencySymbol}\n` +
                  `🚚 *خدمة التوصيل:* شحن سريع لكافة ولايات سلطنة عُمان\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `يرجى تأكيد استلام الطلب وتزويدي برابط الدفع وموعد التوصيل. شكراً لكم!`;
        } else {
            msg = `📱 *New Purchase Order from Bader Phones*\n` +
                  `Hello! I would like to purchase the following items from my cart:\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `${itemsText}\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `💰 *Total Amount:* ${total.toFixed(3)} ${currencySymbol}\n` +
                  `🚚 *Delivery:* Express shipping across Sultanate of Oman\n` +
                  `━━━━━━━━━━━━━━━━━━━━\n` +
                  `Please confirm order availability and provide payment/delivery details. Thank you!`;
        }

        const phone = whatsappPhone || '96897447122';
        const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
        const win = window.open(waUrl, '_blank');
        if (!win || win.closed || typeof win.closed === 'undefined') {
            window.location.href = waUrl;
        }
    };

    // ------------------------------------------------------------------------
    // 6. Sleek 2-Second Corner Toast Notification
    // ------------------------------------------------------------------------
    window.showCornerToast = function (icon, title, desc) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast-card';
        toast.innerHTML = `
            <div class="toast-card-icon">${icon}</div>
            <div class="toast-card-body">
                <div class="toast-card-title">${title}</div>
                <div class="toast-card-desc">${desc}</div>
            </div>
            <div class="toast-progress"></div>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 2000);
    };

    // ------------------------------------------------------------------------
    // 7. Event Handlers, Live Search Autocomplete & Filter Initialization
    // ------------------------------------------------------------------------
    function initializeApp() {
        initTheme();
        updateCartUI();

        // Check for redirect notifications
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('logged_in')) {
            showCornerToast('👋', cfg.shopName || "بدر للهواتف", i18nJs.toastLoggedIn);
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.has('registered')) {
            showCornerToast('🎉', cfg.shopName || "بدر للهواتف", i18nJs.toastRegistered);
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.has('logged_out')) {
            showCornerToast('👋', cfg.shopName || "بدر للهواتف", i18nJs.toastLoggedOut);
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Attach direct click event listeners as additional guarantee
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleSidebar(true);
            });
        }

        const cartNavBtn = document.getElementById('cartNavBtn');
        if (cartNavBtn) {
            cartNavBtn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleCartDrawer(true);
            });
        }

        const sidebarOverlay = document.getElementById('sidebarOverlay');
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                toggleSidebar(false);
            });
        }

        const cartOverlay = document.getElementById('cartOverlay');
        if (cartOverlay) {
            cartOverlay.addEventListener('click', function () {
                toggleCartDrawer(false);
            });
        }

        // Document-level delegated click handler (catches all clicks reliably)
        document.addEventListener('click', function (e) {
            if (e.target.closest('#hamburgerBtn, .btn-hamburger, [data-action="open-sidebar"]')) {
                e.preventDefault();
                toggleSidebar(true);
            } else if (e.target.closest('.sidebar-close-btn, [data-action="close-sidebar"]')) {
                e.preventDefault();
                toggleSidebar(false);
            } else if (e.target.closest('#cartNavBtn, .btn-cart-nav, [data-action="open-cart"]')) {
                e.preventDefault();
                toggleCartDrawer(true);
            } else if (e.target.closest('.cart-close-btn, [data-action="close-cart"]')) {
                e.preventDefault();
                toggleCartDrawer(false);
            }
        });

        // Live Search Elements
        const searchInput = document.getElementById('liveSearchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        const autocompleteDropdown = document.getElementById('searchAutocompleteDropdown');
        const autocompleteList = document.getElementById('autocompleteResultsList');
        const matchCountEl = document.getElementById('autocompleteMatchCount');
        const productCards = document.querySelectorAll('.product-card');
        const noResults = document.getElementById('noSearchResults');
        const filterBtns = document.querySelectorAll('.filter-btn');

        let activeCategory = 'all';

        function renderAutocomplete(query) {
            if (!query || query.length < 1) {
                if (autocompleteDropdown) autocompleteDropdown.classList.remove('active');
                return;
            }

            const matches = catalogProducts.filter(p =>
                (p.name && p.name.toLowerCase().includes(query)) ||
                (p.specs && p.specs.toLowerCase().includes(query)) ||
                (p.category && p.category.toLowerCase().includes(query))
            );

            if (matchCountEl) matchCountEl.textContent = matches.length;

            if (matches.length === 0) {
                if (autocompleteList) {
                    autocompleteList.innerHTML = `
                        <div class="autocomplete-empty">
                            <div style="font-size: 2rem; margin-bottom: 0.35rem;">🔍</div>
                            <div style="font-weight: 700; font-size: 0.9rem;">${i18nJs.noResults}</div>
                        </div>
                    `;
                }
                if (autocompleteDropdown) autocompleteDropdown.classList.add('active');
                return;
            }

            let html = '';
            matches.slice(0, 6).forEach(item => {
                const icon = getDeviceIcon(item.imageType);
                html += `
                    <li class="autocomplete-item" onclick="jumpToProduct('${item.slug}')">
                        <div class="autocomplete-thumb">${icon}</div>
                        <div class="autocomplete-details">
                            <div class="autocomplete-name">${item.name}</div>
                            <div class="autocomplete-specs">${item.specs}</div>
                        </div>
                        <div class="autocomplete-price-col">
                            <div class="autocomplete-price">${item.formattedPrice}</div>
                            <div class="autocomplete-currency">${currencySymbol}</div>
                        </div>
                    </li>
                `;
            });

            if (autocompleteList) autocompleteList.innerHTML = html;
            if (autocompleteDropdown) autocompleteDropdown.classList.add('active');
        }

        function filterGridCatalog() {
            if (!searchInput) return;
            const query = searchInput.value.trim().toLowerCase();
            let visible = 0;

            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'block' : 'none';
            }

            productCards.forEach(card => {
                const cat = card.getAttribute('data-category');
                const name = (card.getAttribute('data-name') || '').toLowerCase();
                const specs = (card.getAttribute('data-specs') || '').toLowerCase();

                const matchCat = (activeCategory === 'all' || cat === activeCategory);
                const matchQuery = (query === '' || name.includes(query) || specs.includes(query));

                if (matchCat && matchQuery) {
                    card.style.display = 'flex';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noResults) {
                noResults.style.display = (visible === 0) ? 'block' : 'none';
            }
        }

        window.jumpToProduct = function (slug) {
            if (autocompleteDropdown) autocompleteDropdown.classList.remove('active');

            const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
            if (allBtn) allBtn.click();

            const targetCard = document.getElementById(`card-${slug}`);
            if (targetCard) {
                targetCard.style.display = 'flex';
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

                targetCard.classList.remove('highlight-pulse');
                void targetCard.offsetWidth;
                targetCard.classList.add('highlight-pulse');

                setTimeout(() => {
                    targetCard.classList.remove('highlight-pulse');
                }, 2000);
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();
                renderAutocomplete(query);
                filterGridCatalog();
            });

            searchInput.addEventListener('focus', function () {
                const query = this.value.trim().toLowerCase();
                if (query.length > 0) renderAutocomplete(query);
            });
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.header-search')) {
                if (autocompleteDropdown) autocompleteDropdown.classList.remove('active');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (autocompleteDropdown) autocompleteDropdown.classList.remove('active');
                toggleSidebar(false);
                toggleCartDrawer(false);
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (autocompleteDropdown) autocompleteDropdown.classList.remove('active');
                filterGridCatalog();
                if (searchInput) searchInput.focus();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeCategory = this.getAttribute('data-filter');
                filterGridCatalog();
            });
        });

        window.selectCategoryFilter = function (categoryId) {
            const target = document.querySelector(`.filter-btn[data-filter="${categoryId}"]`);
            if (target) target.click();
            const sec = document.getElementById('products-section');
            if (sec) sec.scrollIntoView({ behavior: 'smooth' });
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
        initializeApp();
    }
})();
