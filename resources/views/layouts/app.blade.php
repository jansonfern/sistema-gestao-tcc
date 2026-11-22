<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Security headers are sent by middleware -->
    
    <title>@yield('title', 'Clínica Médica')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Google Fonts (Preconnect for security) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (Secure CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Vite for CSS and JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap CSS fallback if Vite fails -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    
    <style nonce="{{ $cspNonce ?? '' }}">
        /* Reset e Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #334155;
        }
        
        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Navbar Security Enhancements */
        .navbar-medical {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar-medical .navbar-brand {
            font-weight: 700;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .navbar-medical .navbar-brand:hover {
            color: #0ea5e9;
            transform: translateY(-1px);
        }
        
        .brand-icon {
            width: 45px;
            height: 45px;
            background: #0ea5e9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.3);
            transition: all 0.3s ease;
        }
        
        .brand-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.4);
        }
        
        .nav-link {
            color: #64748b;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #f1f5f9;
            color: #0ea5e9 !important;
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background: #0ea5e9;
            color: white !important;
        }
        
        /* Dropdown Security */
        .dropdown-menu {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #334155;
        }
        
        .dropdown-item:hover {
            background: #f8fafc;
            color: #0ea5e9;
            transform: translateX(4px);
        }
        
        .user-avatar {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-radius: 50%;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        /* Content Wrapper */
        .content-wrapper {
            flex: 1;
            padding: 2rem 0;
        }
        
        .clean-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Cards */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: white;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #0ea5e9;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.4);
            background: #0284c7;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: #10b981;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-left-color: #ef4444;
        }
        
        /* Footer Styles */
        footer {
            background: #1e293b;
            color: #e2e8f0;
            padding: 3rem 0 2rem;
            margin-top: auto;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: #334155;
        }
        
        footer h5 {
            color: #f8fafc;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        footer p {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 0.25rem 0;
            display: inline-block;
        }
        
        footer a:hover {
            color: #0ea5e9;
            transform: translateX(4px);
        }
        
        footer ul {
            list-style: none;
            padding: 0;
        }
        
        footer ul li {
            margin-bottom: 0.75rem;
        }
        
        footer hr {
            border: none;
            height: 1px;
            background: #334155;
            margin: 2rem 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .brand-icon {
                width: 40px;
                height: 40px;
            }
            
            .nav-link {
                padding: 0.5rem 0.75rem;
                margin: 0.125rem;
            }
            
            footer {
                padding: 2rem 0 1.5rem;
            }
            
            footer h5 {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #0ea5e9;
        }
        
        /* Security: Prevent text selection on sensitive elements */
        .secure-element {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Security: Hide elements that might expose sensitive data in print */
        @media print {
            .no-print {
                display: none !important;
            }
        }

        /* Navbar light theme overrides (app.css forces dark-theme colors) */
        .app-toast-container {
            z-index: 1090;
        }
        .app-toast-icon {
            width: 32px;
            height: 32px;
        }
        .app-toast-icon-success {
            background: rgba(16, 185, 129, 0.12);
        }
        .app-toast-icon-error {
            background: rgba(239, 68, 68, 0.12);
        }
        .navbar-medical {
            background: rgba(255, 255, 255, 0.95) !important;
        }
        .navbar-medical .navbar-brand,
        .navbar-medical .navbar-brand .fw-bold {
            color: #1e293b !important;
        }
        .navbar-medical .nav-link {
            color: #64748b !important;
        }
        .navbar-medical .nav-link:hover,
        .navbar-medical .nav-link.active {
            color: #0ea5e9 !important;
            background: #f1f5f9;
        }
        .navbar-medical .dropdown-toggle .fw-semibold {
            color: #1e293b !important;
        }
        .navbar-medical .dropdown-toggle small {
            color: #94a3b8 !important;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light navbar-medical">
            <div class="container-fluid px-3">
                <a class="navbar-brand d-flex align-items-center me-3" href="{{ route('dashboard') }}">
                    <div class="brand-icon">
                        <i class="fas fa-hospital-alt text-white fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <div class="fw-bold">Clínica Médica</div>
                    </div>
                </a>

                @auth
                    <button class="navbar-toggler border-0 ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('dashboard') }}">
                                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                        </a>
                                    </li>
                                    
                                    @if(auth()->user()->role === 'admin')
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('patients.index') }}">
                                                <i class="fas fa-users me-2"></i> Pacientes
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('doctors.index') }}">
                                                <i class="fas fa-user-md me-2"></i> Médicos
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('schedules.index') }}">
                                                <i class="fas fa-calendar-alt me-2"></i> Agendas
                                            </a>
                                        </li>
                                    @endif
                                    
                                    @if(auth()->user()->role === 'medico')
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('my-schedules') }}">
                                                <i class="fas fa-calendar-check me-2"></i> Minha Agenda
                                            </a>
                                        </li>
                                    @endif
                                    
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('appointments.index') }}">
                                            <i class="fas fa-stethoscope me-2"></i> 
                                            @if(auth()->user()->role === 'paciente')
                                                Minhas Consultas
                                            @else
                                                Consultas
                                            @endif
                                        </a>
                                    </li>
                        </ul>

                        <ul class="navbar-nav ms-lg-3">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                        <small class="text-muted">{{ ucfirst(auth()->user()->role) }}</small>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <div class="dropdown-item-text px-3 py-2">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                    <i class="fas fa-shield-alt text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-primary">{{ ucfirst(auth()->user()->role) }}</div>
                                                    <small class="text-muted">{{ auth()->user()->email }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="fas fa-user-cog me-3 text-primary"></i>
                                            Meus Dados
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                                <i class="fas fa-sign-out-alt me-3"></i>
                                                Sair
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </nav>

        <div class="content-wrapper">
        <div class="toast-container position-fixed top-0 end-0 p-3 app-toast-container">
            @if(session('success'))
                <div class="toast align-items-center border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="4000">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center me-2 flex-shrink-0 app-toast-icon app-toast-icon-success">
                                <i class="fas fa-check text-success"></i>
                            </span>
                            <span><strong>Sucesso!</strong> {{ session('success') }}</span>
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="toast align-items-center border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="5000">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center me-2 flex-shrink-0 app-toast-icon app-toast-icon-error">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                            </span>
                            <span><strong>Erro!</strong> {{ session('error') }}</span>
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
        </div>

            <div class="clean-container">
                @yield('content')
            </div>
        </div>

        <footer>
            <div class="clean-container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="brand-icon me-3">
                                <i class="fas fa-hospital-alt text-white fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Clínica Médica</h5>
                            </div>
                        </div>
                        <p>Plataforma completa e moderna para gestão de clínicas e consultórios médicos, oferecendo eficiência e qualidade no atendimento.</p>
                        <div class="d-flex gap-3 mt-3">
                            <a href="#" class="text-white fs-5"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="text-white fs-5"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white fs-5"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-white fs-5"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5><i class="fas fa-link me-2"></i>Links Rápidos</h5>
                        <ul>
                            <li><a href="{{ route('dashboard') }}"><i class="fas fa-chevron-right me-2"></i>Dashboard</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right me-2"></i>Sobre Nós</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right me-2"></i>Serviços</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right me-2"></i>Preços</a></li>
                            <li><a href="#"><i class="fas fa-chevron-right me-2"></i>Contato</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h5><i class="fas fa-headset me-2"></i>Suporte</h5>
                        <ul>
                            <li><a href="#"><i class="fas fa-book me-2"></i>Documentação</a></li>
                            <li><a href="#"><i class="fas fa-question-circle me-2"></i>Central de Ajuda</a></li>
                            <li><a href="#"><i class="fas fa-video me-2"></i>Tutoriais</a></li>
                            <li><a href="#"><i class="fas fa-comments me-2"></i>Chat Online</a></li>
                            <li><a href="#"><i class="fas fa-envelope me-2"></i>Contato Técnico</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h5><i class="fas fa-map-marker-alt me-2"></i>Contato</h5>
                        <p><i class="fas fa-phone me-2"></i>(11) 1234-5678</p>
                        <p><i class="fas fa-envelope me-2"></i>contato@clinica.com</p>
                        <p><i class="fas fa-clock me-2"></i>Seg-Sex: 8h-18h</p>
                        <p><i class="fas fa-map-pin me-2"></i>São Paulo, SP</p>
                        
                        <div class="mt-3">
                            <a href="#" class="btn btn-primary btn-sm">
                                <i class="fas fa-calendar me-2"></i>Agende uma Demo
                            </a>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2024 Clínica Médica. Todos os direitos reservados.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="#" class="me-3">Política de Privacidade</a>
                        <a href="#" class="me-3">Termos de Uso</a>
                        <a href="#">LGPD</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS fallback if Vite fails -->
    <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.querySelectorAll('.toast').forEach(function (el) {
            new bootstrap.Toast(el).show();
        });

        window.showAppToast = function (message, type) {
            const container = document.querySelector('.app-toast-container');
            if (!container) return;

            const isSuccess = type !== 'error';
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center border-0 shadow';
            toast.setAttribute('role', 'alert');

            const wrap = document.createElement('div');
            wrap.className = 'd-flex';

            const body = document.createElement('div');
            body.className = 'toast-body d-flex align-items-center';

            const icon = document.createElement('span');
            icon.className = 'rounded-circle d-inline-flex align-items-center justify-content-center me-2 flex-shrink-0 app-toast-icon ' + (isSuccess ? 'app-toast-icon-success' : 'app-toast-icon-error');
            const i = document.createElement('i');
            i.className = isSuccess ? 'fas fa-check text-success' : 'fas fa-exclamation-triangle text-danger';
            icon.appendChild(i);

            const text = document.createElement('span');
            const strong = document.createElement('strong');
            strong.textContent = isSuccess ? 'Sucesso! ' : 'Erro! ';
            text.appendChild(strong);
            text.appendChild(document.createTextNode(message));

            body.appendChild(icon);
            body.appendChild(text);

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn-close me-2 m-auto';
            close.setAttribute('data-bs-dismiss', 'toast');

            wrap.appendChild(body);
            wrap.appendChild(close);
            toast.appendChild(wrap);
            container.appendChild(toast);

            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
            new bootstrap.Toast(toast, { delay: isSuccess ? 4000 : 6000 }).show();
        };
    </script>
</body>
</html>