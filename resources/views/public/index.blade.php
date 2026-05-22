<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Transporte Castillo | Transporte de Carga Nacional & Logística</title>
    <meta name="description" content="Transporte Castillo - Empresa líder en transporte de carga nacional, logística y distribución en Perú. Soluciones empresariales seguras, rápidas y confiables.">
    <meta name="keywords" content="transporte de carga, logística, distribución, transporte pesado, carga nacional, Perú, Transporte Castillo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ config('app.url') }}">
    <meta property="og:title" content="Transporte Castillo | Transporte de Carga Nacional & Logística">
    <meta property="og:description" content="Empresa líder en transporte de carga nacional, logística y distribución en Perú.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta name="theme-color" content="#0b1120">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="bg-[#080d1a] text-white antialiased" style="overflow: hidden;">

    <div class="loader" id="page-loader">
        <div class="flex flex-col items-center">
            <div class="loader-logo">TC</div>
            <div class="loader-bar">
                <div class="loader-progress"></div>
            </div>
        </div>
    </div>

    <nav class="navbar fixed top-0 left-0 w-full z-50 px-4 sm:px-6 lg:px-10 py-4" id="navbar">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 text-white no-underline">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-brand-gold to-brand-gold-light flex items-center justify-center font-black text-brand-dark text-sm tracking-wider shadow-[0_4px_12px_rgba(197,160,67,0.25)]">
                    TC
                </div>
                <div>
                    <div class="text-base font-bold leading-tight tracking-tight">Transporte Castillo</div>
                    <div class="text-[10px] text-white/35 tracking-widest uppercase font-semibold">Transporte & Logística</div>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-8">
                <a href="#inicio" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Inicio</a>
                <a href="#nosotros" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Nosotros</a>
                <a href="#servicios" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Servicios</a>
                <a href="#flota" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Flota</a>
                <a href="#ventajas" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Ventajas</a>
                <a href="#contacto" class="nav-link text-white/70 hover:text-white text-sm font-medium no-underline">Contacto</a>
            </div>

            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ route('login') }}" class="btn-primary-outline !py-2.5 !px-5 !text-sm">Acceso interno</a>
            </div>

            <button class="lg:hidden text-white p-2" id="menu-toggle" aria-label="Abrir menú">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </nav>

    <div class="mobile-menu lg:hidden" id="mobile-menu">
        <button class="absolute top-6 right-6 text-white/60 hover:text-white" id="menu-close" aria-label="Cerrar menú">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <a href="#inicio">Inicio</a>
        <a href="#nosotros">Nosotros</a>
        <a href="#servicios">Servicios</a>
        <a href="#flota">Flota</a>
        <a href="#ventajas">Ventajas</a>
        <a href="#contacto">Contacto</a>
        <a href="{{ route('login') }}" class="btn-primary !mt-4">Acceso interno</a>
    </div>

    <section id="inicio" class="relative min-h-screen lg:min-h-[92vh] flex items-center overflow-hidden hero-gradient">
        <div class="absolute inset-0" aria-hidden="true">
            <div class="hero-overlay absolute inset-0"></div>
        </div>

        <canvas id="particles-canvas" aria-hidden="true"></canvas>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 w-full pt-28 pb-20 lg:pt-24 lg:pb-16">
            <div class="hero-layout">
                <div class="hero-copy max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-brand-gold text-[10px] font-semibold tracking-widest uppercase mb-6 fade-in visible">
                        <span class="w-2 h-2 rounded-full bg-brand-gold animate-pulse shadow-[0_0_8px_var(--color-brand-gold)]"></span>
                        Excelencia en transporte desde 2015
                    </div>

                    <h1 class="hero-title text-4xl sm:text-6xl lg:text-6xl xl:text-7xl font-black leading-[0.95] mb-6 tracking-normal fade-in visible" style="transition-delay: 0.1s;">
                        <span class="block">Transporte de Carga</span>
                        <span class="gradient-text block">Nacional</span>
                        <span class="block">Rápido & Seguro</span>
                    </h1>

                    <p class="text-base sm:text-lg text-white/55 max-w-xl mb-10 leading-relaxed fade-in visible" style="transition-delay: 0.2s;">
                        Soluciones logísticas integrales con cobertura nacional.
                        Más de 9 años transportando el progreso de Perú con profesionalismo,
                        puntualidad y total seguridad.
                    </p>

                    <div class="flex flex-wrap gap-4 fade-in visible" style="transition-delay: 0.3s;">
                        <a href="#contacto" class="btn-primary">
                            Solicitar cotización
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="#servicios" class="btn-primary-outline">Nuestros servicios</a>
                    </div>
                </div>

                <div class="hero-visual fade-in visible" style="transition-delay: 0.2s;">
                    <div class="hero-vehicle-frame">
                        <img src="{{ asset('images/fleet/heavy-truck-road.jpg') }}" alt="Camión de carga en ruta nacional" class="hero-vehicle-image" fetchpriority="high" decoding="async">
                    </div>
                    <div class="hero-vehicle-badge hero-vehicle-badge-top">
                        <span>Ruta nacional</span>
                    </div>
                    <div class="hero-vehicle-badge hero-vehicle-badge-bottom">
                        <span>Monitoreo 24/7</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="scroll-indicator">
            <div class="mouse"></div>
            <span>Descubre</span>
        </div>
    </section>

    <section id="nosotros" class="pt-24 pb-28 sm:pt-32 sm:pb-40 px-4 sm:px-6 lg:px-10 section-bg-dark">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="fade-in-left">
                    <span class="inline-block text-brand-gold text-[10px] font-semibold tracking-[0.2em] uppercase mb-4">Quiénes somos</span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black leading-[1.1] mb-6 tracking-tight">
                        Más de 9 años
                        <span class="gradient-text">transportando</span><br>
                        el progreso de Perú
                    </h2>
                    <div class="gold-line mb-8"></div>

                    <div class="space-y-6 text-white/50 leading-relaxed text-sm sm:text-base">
                        <p>
                            <strong class="text-white font-semibold">Transporte Castillo</strong> nació con la visión de 
                            convertirse en un referente del transporte de carga nacional en el país. Desde nuestros inicios, 
                            hemos construido una operación sólida basada en la confianza absoluta, la puntualidad estricta y el compromiso 
                            con la seguridad.
                        </p>
                        <p>
                            Contamos con una flota moderna y diversificada de unidades operadas por profesionales logísticos calificados, 
                            conectando Lima con todas las regiones del país. Cada envío es gestionado bajo rigurosos protocolos, 
                            respaldado por tecnología de monitoreo satelital en tiempo real.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-6 mt-10">
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:border-brand-gold/20 transition-all duration-300">
                            <div class="w-10 h-10 rounded-lg bg-brand-gold/10 flex items-center justify-center text-brand-gold flex-shrink-0 shadow-[0_0_15px_rgba(197,160,67,0.1)]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-white text-sm tracking-tight">Misión</div>
                                <p class="text-xs text-white/40 mt-1 leading-relaxed">Ofrecer soluciones de transporte confiables y personalizadas que impulsen el éxito de cada uno de nuestros clientes.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:border-brand-gold/20 transition-all duration-300">
                            <div class="w-10 h-10 rounded-lg bg-brand-gold/10 flex items-center justify-center text-brand-gold flex-shrink-0 shadow-[0_0_15px_rgba(197,160,67,0.1)]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-white text-sm tracking-tight">Visión</div>
                                <p class="text-xs text-white/40 mt-1 leading-relaxed">Consolidarnos como el socio logístico más confiable, eficiente e innovador del Perú, impulsados por la excelencia operativa.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fade-in-right">
                    <div class="relative pl-6 pb-6">
                        <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.4)]">
                            <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&q=80" alt="Almacén de Transporte Castillo" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" loading="lazy">
                        </div>
                        <div class="absolute bottom-0 left-0 bg-gradient-to-br from-brand-gold to-brand-gold-light rounded-xl p-5 shadow-2xl hidden sm:block border border-white/10">
                            <div class="text-brand-dark font-black text-3xl leading-none">9+</div>
                            <div class="text-brand-dark/70 text-xs font-semibold uppercase tracking-wider mt-1">Años de trayectoria</div>
                        </div>
                        <div class="absolute -top-3 right-3 -bottom-3 -left-3 rounded-2xl border border-brand-gold/20 -z-10"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-12">
                        <div class="text-center p-5 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] hover:border-brand-gold/15 transition-all duration-300">
                            <div class="text-2xl sm:text-3xl font-black gradient-text tracking-tight">+500</div>
                            <div class="text-[10px] text-white/35 font-semibold uppercase tracking-wider mt-1">Clientes</div>
                        </div>
                        <div class="text-center p-5 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] hover:border-brand-gold/15 transition-all duration-300">
                            <div class="text-2xl sm:text-3xl font-black gradient-text tracking-tight">+3K</div>
                            <div class="text-[10px] text-white/35 font-semibold uppercase tracking-wider mt-1">Envíos</div>
                        </div>
                        <div class="text-center p-5 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] hover:border-brand-gold/15 transition-all duration-300">
                            <div class="text-2xl sm:text-3xl font-black gradient-text tracking-tight">24/7</div>
                            <div class="text-[10px] text-white/35 font-semibold uppercase tracking-wider mt-1">Soporte</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="servicios" class="py-28 sm:py-36 px-4 sm:px-6 lg:px-10 section-bg-light">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-20 fade-in">
                <span class="inline-block text-brand-gold text-[10px] font-semibold tracking-[0.2em] uppercase mb-4">Nuestra oferta premium</span>
                <h2 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-[1.1] mb-6 tracking-tight">
                    Servicios <span class="gradient-text">Logísticos</span>
                </h2>
                <p class="text-white/50 max-w-2xl mx-auto text-base sm:text-lg leading-relaxed">
                    Soluciones corporativas de alta precisión diseñadas para superar los estándares logísticos más exigentes del país.
                </p>
                <div class="gold-line-center mt-8"></div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 stagger-children">
                <!-- Servicio 1 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-nacional" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <path d="M17 18H18C18.5523 18 19 17.5523 19 17V11H14V17C14 17.5523 14.4477 18 15 18H16" stroke="url(#grad-nacional)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 11H19L21.5 14H19" stroke="url(#grad-nacional)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17V7C2 6.44772 2.44772 6 3 6H13C13.5523 6 14 6.44772 14 7V17C14 17.5523 13.5523 18 13 18H2V17Z" stroke="url(#grad-nacional)" stroke-width="1.5" stroke-linejoin="round"/>
                            <circle cx="5.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <circle cx="10.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <circle cx="17.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <path d="M6 10H10" stroke="rgba(255,255,255,0.25)" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Transporte de Carga Nacional</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Servicio de transporte terrestre a nivel nacional con cobertura en todas las regiones del Perú. Especialistas en carga general, paletizada y sobredimensionada con puntualidad certificada.
                    </p>
                </div>

                <!-- Servicio 2 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-pesado" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <path d="M4 18V8C4 7.44772 4.44772 7 5 7H14C14.5523 7 15 7.44772 15 8V18" stroke="url(#grad-pesado)" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M15 11H20C20.5523 11 21 11.4477 21 12V18" stroke="url(#grad-pesado)" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="7.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <circle cx="12.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <circle cx="17.5" cy="18.5" r="1.5" fill="#c5a043"/>
                            <path d="M8 11H11" stroke="rgba(255,255,255,0.25)" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M1 18H23" stroke="url(#grad-pesado)" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Transporte Pesado</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Unidades de alto tonelaje configuradas para la industria. Plataformas, camas bajas y camiones listos para transportar maquinaria pesada y cargas sobredimensionadas con máxima rigurosidad técnica.
                    </p>
                </div>

                <!-- Servicio 3 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-logistica" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="url(#grad-logistica)" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="url(#grad-logistica)" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="url(#grad-logistica)" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Logística & Distribución</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Administración integral de la cadena de suministro: almacenamiento estratégico temporal, consolidación inteligente de mercadería y distribución capilar urbana ágil para sucursales o clientes finales.
                    </p>
                </div>

                <!-- Servicio 4 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-segura" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <path d="M12 22C12 22 20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" stroke="url(#grad-segura)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 11L11 13L15 9" stroke="url(#grad-segura)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Carga Segura</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Protocolos avanzados de estiba, trincaje e inmovilización física de carga. Monitoreo constante de vibraciones, estanqueidad y resguardo blindado para cargamento de altísimo valor comercial.
                    </p>
                </div>

                <!-- Servicio 5 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-monitoreo" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <circle cx="12" cy="12" r="10" stroke="url(#grad-monitoreo)" stroke-width="1.5"/>
                            <circle cx="12" cy="12" r="4" stroke="url(#grad-monitoreo)" stroke-width="1.5"/>
                            <path d="M12 2V6" stroke="#c5a043" stroke-width="1.5"/>
                            <path d="M12 18V22" stroke="#c5a043" stroke-width="1.5"/>
                            <path d="M2 12H6" stroke="#c5a043" stroke-width="1.5"/>
                            <path d="M18 12H22" stroke="#c5a043" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Monitoreo & Seguimiento</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Acceso a plataforma GPS de última generación. Seguimiento satelital ininterrumpido en tiempo real, alertas de desvío de ruta y reporte digital instantáneo con confirmaciones digitales.
                    </p>
                </div>

                <!-- Servicio 6 -->
                <div class="service-card glass-card rounded-2xl p-8 border border-white/5 bg-white/[0.01] hover:border-brand-gold/30 hover:bg-brand-dark/40 transition-all duration-500">
                    <div class="service-icon mb-6 text-brand-gold">
                        <svg class="w-12 h-12" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="grad-rutas" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#c5a043" />
                                    <stop offset="100%" stop-color="#f3e5ab" />
                                </linearGradient>
                            </defs>
                            <path d="M9 20L3 17V4L9 7M9 20L15 17M9 20V7M15 17L21 20V7L15 4M15 17V4M9 7L15 4" stroke="url(#grad-rutas)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-white transition-colors">Rutas Nacionales</h3>
                    <p class="text-white/45 text-sm leading-relaxed">
                        Conectividad fluida por las principales arterias viales de la Costa, Sierra y Selva. Rutas troncales optimizadas de Lima a Arequipa, Trujillo, Piura, Cusco, Iquitos y más regiones del país.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="flota" class="py-28 sm:py-36 px-4 sm:px-6 lg:px-10 section-bg-dark">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12 fade-in">
                <span class="inline-block text-brand-gold text-[10px] font-semibold tracking-[0.2em] uppercase mb-4">Nuestra flota</span>
                <h2 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-[1.1] mb-6 tracking-tight">
                    Unidades <span class="gradient-text">Modernas</span>
                </h2>
                <p class="text-white/50 max-w-2xl mx-auto text-base sm:text-lg leading-relaxed">
                    Flota diversificada en óptimas condiciones operativas y equipada con tecnología de punta para garantizar la eficiencia.
                </p>
                <div class="gold-line-center mt-8"></div>
            </div>

            <!-- Filtros de la Flota -->
            <div class="flex flex-wrap justify-center gap-3 mb-16 fade-in">
                <button class="filter-btn active px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider transition-all duration-300 border border-white/5 bg-white/5 text-white cursor-pointer hover:border-brand-gold/30" data-filter="all">
                    Todas
                </button>
                <button class="filter-btn px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider transition-all duration-300 border border-white/5 bg-transparent text-white/60 cursor-pointer hover:border-brand-gold/30 hover:text-white" data-filter="carga-pesada">
                    Carga Pesada
                </button>
                <button class="filter-btn px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider transition-all duration-300 border border-white/5 bg-transparent text-white/60 cursor-pointer hover:border-brand-gold/30 hover:text-white" data-filter="distribucion">
                    Distribución
                </button>
                <button class="filter-btn px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider transition-all duration-300 border border-white/5 bg-transparent text-white/60 cursor-pointer hover:border-brand-gold/30 hover:text-white" data-filter="logistica">
                    Logística
                </button>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6" id="gallery-grid">
                <!-- Item 1 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="carga-pesada">
                    <img src="{{ asset('images/fleet/heavy-truck-road.jpg') }}" alt="Tractor Scania Carga Pesada" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Carga Pesada</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Camión Escala Premium</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Configuración Scania para rutas nacionales.</p>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="logistica">
                    <img src="{{ asset('images/fleet/warehouse-operations.jpg') }}" alt="Almacén Logístico Integrado" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Logística</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Centro de Consolidación</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Almacenamiento temporal seguro de mercaderías.</p>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="carga-pesada">
                    <img src="{{ asset('images/fleet/truck-warehouse-loading.jpg') }}" alt="Unidad de Carga Pesada en Muelle" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Carga Pesada</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Remolque Multiejes</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Arrastre de plataformas sobredimensionadas.</p>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="distribucion">
                    <img src="{{ asset('images/fleet/distribution-fleet.jpg') }}" alt="Flotilla de Distribución" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Distribución</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Flota Corporativa</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Reparto urbano y capilar con agilidad.</p>
                    </div>
                </div>

                <!-- Item 5 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="carga-pesada">
                    <img src="{{ asset('images/fleet/night-operations.jpg') }}" alt="Camión Pesado en Ruta" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Carga Pesada</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Tractor Volvo FMX</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Preparado para las condiciones andinas más duras.</p>
                    </div>
                </div>

                <!-- Item 6 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="distribucion">
                    <img src="{{ asset('images/fleet/truck-highway-speed.jpg') }}" alt="Muelle de carga y descarga" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Distribución</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Operación de Carga</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Flujo constante de mercadería optimizado.</p>
                    </div>
                </div>

                <!-- Item 7 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="logistica">
                    <img src="{{ asset('images/fleet/truck-on-road-pexels.jpg') }}" alt="Operaciones Logísticas de Almacén" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Logística</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Gestión de Inventario</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Trazabilidad completa mediante software WMS.</p>
                    </div>
                </div>

                <!-- Item 8 -->
                <div class="gallery-item rounded-2xl overflow-hidden aspect-[4/3] relative group border border-white/5 shadow-lg bg-white/[0.01] transition-all duration-500" data-category="carga-pesada">
                    <img src="{{ asset('images/fleet/trucks-highway-sunset.jpg') }}" alt="Luces de Camión en la Noche" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                        <span class="text-brand-gold text-[9px] font-bold uppercase tracking-wider mb-2">Carga Pesada</span>
                        <h4 class="text-white font-bold text-base mb-1 leading-tight">Operación Nocturna 24/7</h4>
                        <p class="text-white/60 text-[11px] leading-relaxed">Monitoreo continuo para tránsito ininterrumpido.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="ventajas" class="py-28 sm:py-36 px-4 sm:px-6 lg:px-10 section-bg-light relative overflow-hidden">
        <div class="absolute inset-0 opacity-[0.03]">
            <div class="absolute top-10 left-10 w-64 h-64 bg-[#c9952b] rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-[#c9952b] rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-[#c9952b] text-xs font-semibold tracking-[0.2em] uppercase mb-4">Por qué elegirnos</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    Ventajas <span class="gradient-text">Competitivas</span>
                </h2>
                <p class="text-white/50 max-w-xl mx-auto text-lg">
                    Factores que nos diferencian y nos convierten en el socio logístico ideal para tu empresa.
                </p>
                <div class="gold-line-center mt-6"></div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger-children">
                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Puntualidad Garantizada</h4>
                        <p class="text-sm text-white/45">Cumplimos los tiempos de entrega acordados con precisión y profesionalismo.</p>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Seguridad Total</h4>
                        <p class="text-sm text-white/45">Protocolos de seguridad, monitoreo GPS y carga asegurada en todo momento.</p>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Cobertura Nacional</h4>
                        <p class="text-sm text-white/45">Presencia operativa en todas las regiones del Perú con rutas consolidadas.</p>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Experiencia Comprobada</h4>
                        <p class="text-sm text-white/45">Más de 9 años en el sector con miles de operaciones exitosas a nivel nacional.</p>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Atención Personalizada</h4>
                        <p class="text-sm text-white/45">Ejecutivos de cuenta asignados para cada cliente corporativo con soporte dedicado.</p>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Soporte Logístico 24/7</h4>
                        <p class="text-sm text-white/45">Asistencia permanente, monitoreo en tiempo real y reportes de estado de entrega.</p>
                    </div>
                </div>
            </div>

            <div class="premium-counters-container grid grid-cols-2 sm:grid-cols-4 gap-6 mt-16 p-8">
                <div class="counter-item">
                    <div class="counter gradient-text" data-target="9" data-duration="1500">0</div>
                    <div class="counter-label">Años de experiencia</div>
                </div>
                <div class="counter-item">
                    <div class="counter gradient-text" data-target="3000" data-duration="2500">0</div>
                    <div class="counter-label">Envíos realizados</div>
                </div>
                <div class="counter-item">
                    <div class="counter gradient-text" data-target="500" data-duration="2000">0</div>
                    <div class="counter-label">Clientes satisfechos</div>
                </div>
                <div class="counter-item">
                    <div class="counter gradient-text" data-target="24" data-duration="1000">0</div>
                    <div class="counter-label">Soporte / hrs</div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonios" class="py-28 sm:py-36 px-4 sm:px-6 lg:px-10 section-bg-dark relative overflow-hidden">
        <div class="absolute inset-0 opacity-[0.02] pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-gold rounded-full blur-[120px]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-20 fade-in">
                <span class="inline-block text-brand-gold text-[10px] font-semibold tracking-[0.2em] uppercase mb-4">Confianza</span>
                <h2 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-[1.1] mb-6 tracking-tight">
                    Lo que dicen <span class="gradient-text">nuestros clientes</span>
                </h2>
                <p class="text-white/50 max-w-2xl mx-auto text-base sm:text-lg leading-relaxed">
                    La satisfacción y confianza de las grandes empresas que respaldan nuestra impecable trayectoria.
                </p>
                <div class="gold-line-center mt-8"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8 stagger-children">
                <!-- Testimonio 1 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1.5 mb-6">
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-white/60 text-sm leading-relaxed mb-8 italic">
                        "Transporte Castillo ha sido un socio estratégico clave para nuestra distribución nacional. Su puntualidad rigurosa y alto profesionalismo nos ha permitido expandirnos con absoluta tranquilidad."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="testimonial-avatar-ring">
                            <div class="w-10 h-10 rounded-full bg-[#030712] flex items-center justify-center text-sm font-bold text-brand-gold tracking-tight border border-white/5">JD</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white tracking-tight">Juan Delgado</div>
                            <div class="text-[10px] text-white/35 font-medium uppercase tracking-wider mt-0.5">Gerente Logístico - Corporación del Sur</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonio 2 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1.5 mb-6">
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-white/60 text-sm leading-relaxed mb-8 italic">
                        "La rigurosa seguridad con la que gestionan y resguardan cada uno de nuestros envíos es sobresaliente. Llevamos 3 años operando juntos y el resultado siempre ha sido impecable."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="testimonial-avatar-ring">
                            <div class="w-10 h-10 rounded-full bg-[#030712] flex items-center justify-center text-sm font-bold text-brand-gold tracking-tight border border-white/5">MR</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white tracking-tight">María Ríos</div>
                            <div class="text-[10px] text-white/35 font-medium uppercase tracking-wider mt-0.5">Jefa de Operaciones - Grupo Norte</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonio 3 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1.5 mb-6">
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-white/60 text-sm leading-relaxed mb-8 italic">
                        "El sistema de monitoreo satelital en tiempo real de nuestras cargas nos brinda una tranquilidad invaluable. Transporte Castillo es sinónimo de eficiencia, seguridad y total confianza."
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="testimonial-avatar-ring">
                            <div class="w-10 h-10 rounded-full bg-[#030712] flex items-center justify-center text-sm font-bold text-brand-gold tracking-tight border border-white/5">CP</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white tracking-tight">Carlos Paredes</div>
                            <div class="text-[10px] text-white/35 font-medium uppercase tracking-wider mt-0.5">CEO - Importaciones del Pacífico</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 fade-in border-t border-white/5 pt-16">
                <div class="trust-badge hover:scale-105 transition-all duration-300">
                    <div class="badge-number gradient-text">99.8%</div>
                    <div class="badge-label">Entregas exitosas</div>
                </div>
                <div class="trust-badge hover:scale-105 transition-all duration-300">
                    <div class="badge-number gradient-text">+50</div>
                    <div class="badge-label">Empresas asociadas</div>
                </div>
                <div class="trust-badge hover:scale-105 transition-all duration-300">
                    <div class="badge-number gradient-text">15</div>
                    <div class="badge-label">Unidades operativas</div>
                </div>
                <div class="trust-badge hover:scale-105 transition-all duration-300">
                    <div class="badge-number gradient-text">100%</div>
                    <div class="badge-label">Cobertura nacional</div>
                </div>
            </div>
        </div>
    </section>

    <section id="contacto" class="py-28 sm:py-36 px-4 sm:px-6 lg:px-10 section-bg-dark">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-[#c9952b] text-xs font-semibold tracking-[0.2em] uppercase mb-4">Contacto</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    Hablemos de tu <span class="gradient-text">próximo envío</span>
                </h2>
                <p class="text-white/50 max-w-xl mx-auto text-lg">
                    Solicita una cotización personalizada o consulta nuestros servicios. Te responderemos en menos de 24 horas.
                </p>
                <div class="gold-line-center mt-6"></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <div class="fade-in-left">
                    <form id="contact-form" class="space-y-5">
                        <div class="grid sm:grid-cols-2 gap-5">
                            <input type="text" class="contact-input" name="nombre" placeholder="Nombre completo" required>
                            <input type="email" class="contact-input" name="email" placeholder="Correo electrónico" required>
                        </div>
                        <input type="tel" class="contact-input" name="telefono" placeholder="Teléfono" required>
                        <input type="text" class="contact-input" name="empresa" placeholder="Empresa (opcional)">
                        <select class="contact-input" name="servicio" required>
                            <option value="" disabled selected hidden>Selecciona un servicio</option>
                            <option value="transporte-nacional">Transporte de carga nacional</option>
                            <option value="transporte-pesado">Transporte pesado</option>
                            <option value="logistica">Logística y distribución</option>
                            <option value="carga-segura">Carga segura</option>
                            <option value="otro">Otro</option>
                        </select>
                        <textarea class="contact-input" name="mensaje" rows="4" placeholder="Cuéntanos sobre tu carga, origen, destino y requerimientos" required></textarea>

                        <div class="success-message" id="form-success">
                            Mensaje enviado correctamente. Te contactaremos pronto.
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">
                            Enviar mensaje
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="fade-in-right space-y-8">
                    <div class="glass-card rounded-2xl p-8">
                        <h3 class="text-lg font-bold mb-6">Información de contacto</h3>
                        <div class="space-y-5">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-[#c9952b]/10 flex items-center justify-center text-[#c9952b] flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-white/40 font-medium uppercase tracking-wider">Teléfono</div>
                                    <a href="tel:+51999999999" class="text-white font-medium hover:text-[#c9952b] transition-colors no-underline">+51 999 999 999</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-[#c9952b]/10 flex items-center justify-center text-[#c9952b] flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-white/40 font-medium uppercase tracking-wider">Correo</div>
                                    <a href="mailto:info@transcastillo.com" class="text-white font-medium hover:text-[#c9952b] transition-colors no-underline">info@transcastillo.com</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-[#c9952b]/10 flex items-center justify-center text-[#c9952b] flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-white/40 font-medium uppercase tracking-wider">Ubicación</div>
                                    <div class="text-white font-medium">Lima, Perú</div>
                                    <div class="text-white/40 text-sm">Atención: Lun - Sáb 8:00 AM - 6:00 PM</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-8">
                        <h3 class="text-lg font-bold mb-4">Síguenos</h3>
                        <p class="text-white/45 text-sm mb-5">Conoce más sobre nuestras operaciones y flota en redes sociales.</p>
                        <div class="flex gap-3">
                            <a href="https://www.facebook.com/transcastillo" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="social-link" aria-label="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a href="https://wa.me/51999999999" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="WhatsApp">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d124711.234567!2d-77.05!3d-12.05!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTLCsDAzJzAwLjAiUyA3N8KwMDUnMDAuMCJX!5e0!3m2!1ses!2spe!4v1" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Ubicación Transporte Castillo"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-[#070b14] border-t border-white/5 px-4 sm:px-6 lg:px-10 py-16">
        <div class="max-w-7xl mx-auto">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#c9952b] to-[#e8c06a] flex items-center justify-center font-bold text-[#0b1120] text-sm">
                            TC
                        </div>
                        <div>
                            <div class="text-base font-bold leading-tight">Transporte Castillo</div>
                            <div class="text-[11px] text-white/30 tracking-widest uppercase">Transporte & Logística</div>
                        </div>
                    </div>
                    <p class="text-white/40 text-sm leading-relaxed">
                        Soluciones logísticas integrales con cobertura nacional. Transporte de carga seguro, rápido y profesional.
                    </p>
                </div>

                <div>
                    <h4 class="text-xs font-semibold tracking-[0.15em] uppercase text-white/50 mb-5">Servicios</h4>
                    <div class="space-y-3">
                        <a href="#servicios" class="footer-link block">Carga nacional</a>
                        <a href="#servicios" class="footer-link block">Transporte pesado</a>
                        <a href="#servicios" class="footer-link block">Logística y distribución</a>
                        <a href="#servicios" class="footer-link block">Carga segura</a>
                        <a href="#servicios" class="footer-link block">Monitoreo GPS</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold tracking-[0.15em] uppercase text-white/50 mb-5">Empresa</h4>
                    <div class="space-y-3">
                        <a href="#nosotros" class="footer-link block">Nosotros</a>
                        <a href="#flota" class="footer-link block">Flota</a>
                        <a href="#ventajas" class="footer-link block">Ventajas</a>
                        <a href="#contacto" class="footer-link block">Contacto</a>
                        <a href="{{ route('login') }}" class="footer-link block">Acceso interno</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold tracking-[0.15em] uppercase text-white/50 mb-5">Contacto</h4>
                    <div class="space-y-3 text-sm">
                        <div class="text-white/40">
                            <span class="text-white/60">Tel:</span>
                            <a href="tel:+51999999999" class="footer-link ml-1">+51 999 999 999</a>
                        </div>
                        <div class="text-white/40">
                            <span class="text-white/60">Email:</span>
                            <a href="mailto:info@transcastillo.com" class="footer-link ml-1">info@transcastillo.com</a>
                        </div>
                        <div class="text-white/40">
                            <span class="text-white/60">Ubicación:</span>
                            <span class="text-white/50 ml-1">Lima, Perú</span>
                        </div>
                        <div class="text-white/40">
                            <span class="text-white/60">Horario:</span>
                            <span class="text-white/50 ml-1">Lun - Sáb 8:00 AM - 6:00 PM</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-white/30 text-sm">
                    &copy; {{ date('Y') }} Transporte Castillo. Todos los derechos reservados.
                </div>
                <div class="flex gap-4">
                    <a href="https://www.facebook.com/transcastillo" target="_blank" rel="noopener noreferrer" class="text-white/30 hover:text-[#c9952b] transition-colors" aria-label="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="text-white/30 hover:text-[#c9952b] transition-colors" aria-label="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="#" class="text-white/30 hover:text-[#c9952b] transition-colors" aria-label="LinkedIn">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/51999999999" target="_blank" rel="noopener noreferrer" class="whatsapp-float" aria-label="WhatsApp">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const canvas = document.getElementById("particles-canvas");
            if (!canvas) return;

            const ctx = canvas.getContext("2d");
            let particles = [];
            let animationId;

            function resize() {
                const rect = canvas.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                canvas.style.width = rect.width + "px";
                canvas.style.height = rect.height + "px";
            }

            function createParticles() {
                const w = canvas.clientWidth;
                const h = canvas.clientHeight;
                particles = Array.from({ length: 60 }, () => ({
                    x: Math.random() * w,
                    y: Math.random() * h,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    r: Math.random() * 2 + 0.5,
                    a: Math.random() * 0.4 + 0.1,
                }));
            }

            function draw() {
                const w = canvas.clientWidth;
                const h = canvas.clientHeight;
                ctx.clearRect(0, 0, w, h);

                particles.forEach((p) => {
                    p.x += p.vx;
                    p.y += p.vy;

                    if (p.x < 0) p.x = w;
                    if (p.x > w) p.x = 0;
                    if (p.y < 0) p.y = h;
                    if (p.y > h) p.y = 0;

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(201, 149, 43, ${p.a})`;
                    ctx.fill();
                });

                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < 120) {
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = `rgba(201, 149, 43, ${0.08 * (1 - dist / 120)})`;
                            ctx.stroke();
                        }
                    }
                }

                animationId = requestAnimationFrame(draw);
            }

            resize();
            createParticles();
            draw();

            window.addEventListener("resize", () => {
                resize();
                createParticles();
            });
        });
    </script>
</body>
</html>
