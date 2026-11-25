import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    GraduationCap,
    BookOpen,
    Award,
    Star,
    ShieldCheck,
    Search,
    ArrowRight,
    ArrowUpRight,
    Coins,
    Users,
    FileText,
    CheckCircle,
    Info,
    X,
    Lock,
    Bookmark,
    CheckCircle2,
} from 'lucide-react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    const [showWalletModal, setShowWalletModal] = useState(false);

    return (
        <>
            <Head title="AGU Academic Hub — Cổng Tri thức & Học thuật Đại học An Giang" />

            <div className="min-h-screen bg-slate-50 text-slate-800 flex flex-col antialiased selection:bg-brand-primary selection:text-white">
                {/* Top Navigation */}
                <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                        <Link
                            href={auth?.user ? route('dashboard') : route('home')}
                            className="flex items-center gap-3 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary rounded-lg p-0.5"
                        >
                            <div className="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center shrink-0">
                                <ApplicationLogo className="w-full h-full group-hover:scale-105 transition-transform" />
                            </div>
                            <div>
                                <span className="block text-base font-bold tracking-tight text-slate-900 leading-tight group-hover:text-brand-primary transition-colors">
                                    AGU Academic Hub
                                </span>
                                <span className="block text-xs font-medium text-slate-500">
                                    Đại học An Giang — ĐHQG-HCM
                                </span>
                            </div>
                        </Link>

                        <nav className="flex items-center gap-2 sm:gap-3">
                            <Link
                                href={route('faculties.index')}
                                className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-600 hover:text-brand-primary transition-colors rounded-lg hover:bg-slate-100/80"
                            >
                                <BookOpen className="h-4 w-4 text-slate-400" />
                                Khoa & Môn học
                            </Link>
                            <Link
                                href={route('document-requests.index')}
                                className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-600 hover:text-brand-primary transition-colors rounded-lg hover:bg-slate-100/80"
                            >
                                <Award className="h-4 w-4 text-slate-400" />
                                Săn thưởng Bounty
                            </Link>

                            {auth?.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-brand-primary/95 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2"
                                >
                                    <span>Bảng điều khiển</span>
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={route('login')}
                                        className="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-700 hover:text-brand-primary hover:bg-slate-100 transition-colors"
                                    >
                                        Đăng nhập
                                    </Link>
                                    <Link
                                        href={route('alumni.register')}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition-colors"
                                    >
                                        <Users className="h-4 w-4 text-slate-500" />
                                        <span>Cựu sinh viên</span>
                                    </Link>
                                </div>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    {/* Hero Section */}
                    <section className="border-b border-slate-200 bg-white py-14 sm:py-20">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                                {/* Left Content */}
                                <div className="lg:col-span-7 text-left">
                                    {/* Context Label */}
                                    <div className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 mb-6">
                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-600" />
                                        <span>Cổng thông tin học liệu chính thức · AGU</span>
                                    </div>

                                    <h1 className="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl leading-tight">
                                        Chia sẻ tài liệu, kết nối học thuật & nâng tầm tri thức
                                    </h1>

                                    <p className="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl">
                                        Hệ thống số hóa đề cương, bài giảng, slide và đề thi có thẩm định của Trường Đại học An Giang. Hỗ trợ sinh viên tra cứu minh bạch và trao đổi kinh nghiệm môn học hiệu quả.
                                    </p>

                                    {/* CTAs */}
                                    <div className="mt-8 flex flex-wrap items-center gap-3">
                                        <Link
                                            href={route('faculties.index')}
                                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-xs hover:bg-brand-primary/95 transition-colors group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary"
                                        >
                                            <Search className="h-4 w-4" />
                                            <span>Tra cứu kho môn học</span>
                                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                        </Link>

                                        {auth?.user ? (
                                            <Link
                                                href={route('dashboard')}
                                                className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition-colors"
                                            >
                                                <span>Bảng điều khiển cá nhân</span>
                                            </Link>
                                        ) : (
                                            <Link
                                                href={route('login')}
                                                className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition-colors"
                                            >
                                                <GraduationCap className="h-4 w-4 text-slate-500" />
                                                <span>Đăng nhập sinh viên AGU</span>
                                            </Link>
                                        )}
                                    </div>

                                    {/* Quiet Trust Bar */}
                                    <div className="mt-10 pt-6 border-t border-slate-100 flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-slate-500">
                                        <span className="flex items-center gap-1.5">
                                            <CheckCircle className="h-3.5 w-3.5 text-emerald-600" />
                                            Kiểm duyệt liêm chính học thuật
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <CheckCircle className="h-3.5 w-3.5 text-emerald-600" />
                                            Theo chuẩn khung chương trình AGU
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <CheckCircle className="h-3.5 w-3.5 text-emerald-600" />
                                            Mã băm SHA-256 chống trùng lặp
                                        </span>
                                    </div>
                                </div>

                                {/* Right: Real Academic Specimen Preview (Craft Moment) */}
                                <div className="lg:col-span-5">
                                    <div className="rounded-xl border border-slate-200 bg-slate-50/70 p-5 shadow-xs">
                                        <div className="flex items-center justify-between pb-3 border-b border-slate-200 text-xs text-slate-500">
                                            <div className="flex items-center gap-1.5 font-medium text-slate-700">
                                                <FileText className="h-4 w-4 text-brand-primary" />
                                                <span>Tài liệu mẫu đã kiểm duyệt</span>
                                            </div>
                                            <span className="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Chính thức
                                            </span>
                                        </div>

                                        <div className="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <span className="text-[11px] font-semibold text-brand-primary uppercase tracking-wide">
                                                        KHOA CÔNG NGHỆ THÔNG TIN
                                                    </span>
                                                    <h4 className="mt-1 text-sm font-semibold text-slate-900 leading-snug">
                                                        Đề cương chi tiết & Ngân hàng câu hỏi Lập trình hướng đối tượng
                                                    </h4>
                                                    <p className="mt-1 text-xs text-slate-500">
                                                        Mã học phần: <span className="font-mono font-medium text-slate-700">COS201</span> · 3 Tín chỉ
                                                    </p>
                                                </div>
                                                <Bookmark className="h-4 w-4 text-slate-400 shrink-0" />
                                            </div>

                                            <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-mono text-[11px] text-slate-400">sha256:8b4e...9f12</span>
                                                </div>
                                                <span className="text-slate-600 font-medium">Học kỳ 1 · 2026-2027</span>
                                            </div>
                                        </div>

                                        <div className="mt-3 flex items-center justify-between px-1 text-xs text-slate-500">
                                            <span>Xác thực bởi Ban Thư viện AGU</span>
                                            <Link href={route('faculties.index')} className="text-brand-primary font-medium hover:underline inline-flex items-center gap-1">
                                                <span>Xem 240+ tài liệu khác</span>
                                                <ArrowRight className="h-3 w-3" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Features & Cards Section (100% Functional & Unslopped) */}
                    <section className="py-14 sm:py-18 bg-slate-50">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="max-w-2xl mb-10">
                                <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Phân hệ học thuật
                                </h2>
                                <h3 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                                    Các cổng dịch vụ trọng tâm cho sinh viên
                                </h3>
                                <p className="mt-2 text-sm text-slate-600">
                                    Chọn phân hệ để bắt đầu tra cứu đề cương, đặt hàng tài liệu hoặc tham khảo đánh giá môn học.
                                </p>
                            </div>

                            {/* 4 Clickable Functional Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                {/* Card 1: Kho Học Liệu Số */}
                                <Link
                                    href={route('faculties.index')}
                                    className="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary shadow-xs"
                                >
                                    <div>
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 mb-4 transition-colors group-hover:bg-brand-primary group-hover:text-white">
                                            <FileText className="h-5 w-5" />
                                        </div>
                                        <h4 className="text-base font-semibold text-slate-900 group-hover:text-brand-primary transition-colors">
                                            Kho Học Liệu Số
                                        </h4>
                                        <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                            Đầy đủ đề cương, slide bài giảng và đề thi các học kỳ trước được tổ chức khoa học theo 6+ Khoa và Chuyên ngành.
                                        </p>
                                    </div>
                                    <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-brand-primary">
                                        <span>Tra cứu theo Khoa & Môn</span>
                                        <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                    </div>
                                </Link>

                                {/* Card 2: Săn Thưởng Bounty */}
                                <Link
                                    href={route('document-requests.index')}
                                    className="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary shadow-xs"
                                >
                                    <div>
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 mb-4 transition-colors group-hover:bg-brand-primary group-hover:text-white">
                                            <Award className="h-5 w-5" />
                                        </div>
                                        <h4 className="text-base font-semibold text-slate-900 group-hover:text-brand-primary transition-colors">
                                            Săn Thưởng Bounty
                                        </h4>
                                        <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                            Đặt hàng tài liệu hiếm bằng điểm cọc học thuật. Sinh viên khóa trên và cựu sinh viên đóng góp để nhận giải ngân điểm thưởng.
                                        </p>
                                    </div>
                                    <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-brand-primary">
                                        <span>Xem danh sách yêu cầu</span>
                                        <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                    </div>
                                </Link>

                                {/* Card 3: Đánh Giá Môn Học */}
                                <Link
                                    href={route('courses.index')}
                                    className="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary shadow-xs"
                                >
                                    <div>
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 mb-4 transition-colors group-hover:bg-brand-primary group-hover:text-white">
                                            <Star className="h-5 w-5" />
                                        </div>
                                        <h4 className="text-base font-semibold text-slate-900 group-hover:text-brand-primary transition-colors">
                                            Đánh Giá Môn Học
                                        </h4>
                                        <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                            Kinh nghiệm thực tiễn về độ khó, phương pháp ôn tập và khối lượng bài tập. Bộ lọc từ ngữ tự động bảo đảm văn hóa học đường.
                                        </p>
                                    </div>
                                    <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-brand-primary">
                                        <span>Xem nhận xét theo môn</span>
                                        <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                    </div>
                                </Link>

                                {/* Card 4: Ví Điểm Học Thuật */}
                                {auth?.user ? (
                                    <Link
                                        href={route('wallet.index')}
                                        className="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary shadow-xs"
                                    >
                                        <div>
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 mb-4 transition-colors group-hover:bg-brand-primary group-hover:text-white">
                                                <Coins className="h-5 w-5" />
                                            </div>
                                            <h4 className="text-base font-semibold text-slate-900 group-hover:text-brand-primary transition-colors">
                                                Ví Điểm Học Thuật
                                            </h4>
                                            <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                                Theo dõi điểm cọc, thưởng đóng góp và lịch sử biến động số dư điểm học thuật cá nhân.
                                            </p>
                                        </div>
                                        <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-brand-primary">
                                            <span>Xem ví cá nhân</span>
                                            <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                        </div>
                                    </Link>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setShowWalletModal(true)}
                                        className="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 text-left transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary shadow-xs cursor-pointer"
                                    >
                                        <div>
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700 mb-4 transition-colors group-hover:bg-brand-primary group-hover:text-white">
                                                <Coins className="h-5 w-5" />
                                            </div>
                                            <h4 className="text-base font-semibold text-slate-900 group-hover:text-brand-primary transition-colors">
                                                Ví Điểm Học Thuật
                                            </h4>
                                            <p className="mt-2 text-xs leading-relaxed text-slate-600">
                                                Cơ chế ghi nhận đóng góp tài liệu, lượt thích và chia sẻ giá trị trong cộng đồng sinh viên AGU.
                                            </p>
                                        </div>
                                        <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-slate-700 group-hover:text-brand-primary">
                                            <span>Xem quy chế tính điểm</span>
                                            <Info className="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
                                        </div>
                                    </button>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Stats & Quality Control Section */}
                    <section className="border-y border-slate-200 bg-white py-14">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                                <div className="p-6 rounded-xl border border-slate-200 bg-slate-50/50">
                                    <div className="font-mono text-3xl font-bold tracking-tight text-slate-900 tabular-nums">6+</div>
                                    <div className="mt-2 text-sm font-semibold text-slate-900">Khoa Đào tạo Chuyên ngành</div>
                                    <p className="mt-1 text-xs text-slate-500">CNTT, Kinh tế, Sư phạm, Nông nghiệp, KHXH&NV, Ngoại ngữ</p>
                                </div>
                                <div className="p-6 rounded-xl border border-slate-200 bg-slate-50/50">
                                    <div className="font-mono text-3xl font-bold tracking-tight text-slate-900 tabular-nums">100%</div>
                                    <div className="mt-2 text-sm font-semibold text-slate-900">Kiểm duyệt Liêm chính</div>
                                    <p className="mt-1 text-xs text-slate-500">Chống trùng lặp mã băm SHA-256 và quy trình hậu kiểm nghiêm ngặt</p>
                                </div>
                                <div className="p-6 rounded-xl border border-slate-200 bg-slate-50/50">
                                    <div className="font-mono text-3xl font-bold tracking-tight text-slate-900 tabular-nums">2-Chiều</div>
                                    <div className="mt-2 text-sm font-semibold text-slate-900">Kết nối Sinh viên & Cựu sinh viên</div>
                                    <p className="mt-1 text-xs text-slate-500">Hỗ trợ truyền trao kinh nghiệm môn học và nguồn tài liệu hiếm</p>
                                </div>
                            </div>

                            {/* Integrity Notice Banner */}
                            <div className="mt-10 rounded-xl border border-slate-200 bg-slate-50 p-6 flex flex-col sm:flex-row items-center gap-5">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <ShieldCheck className="h-6 w-6" />
                                </div>
                                <div className="flex-1 text-center sm:text-left">
                                    <h4 className="text-sm font-semibold text-slate-900">
                                        Cam kết Liêm chính Học thuật AGU
                                    </h4>
                                    <p className="mt-1 text-xs text-slate-600 leading-relaxed">
                                        Nghiêm cấm phát tán đề thi bí mật chưa công bố hoặc vi phạm bản quyền tác giả. Mọi tài liệu trước khi hiển thị đều qua khâu thẩm duyệt của Ban Thư ký Học thuật.
                                    </p>
                                </div>
                                <div className="shrink-0">
                                    <Link
                                        href={route('faculties.index')}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-xs hover:bg-slate-100 transition-colors"
                                    >
                                        <span>Bắt đầu tra cứu</span>
                                        <ArrowRight className="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Quiet Academic Footer */}
                <footer className="border-t border-slate-200 bg-white py-12 text-xs text-slate-500">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                            <div className="md:col-span-2">
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="w-6 h-6 flex items-center justify-center shrink-0">
                                        <ApplicationLogo className="w-full h-full" />
                                    </div>
                                    <span className="font-semibold text-slate-900 text-sm">
                                        Trường Đại học An Giang — ĐHQG-HCM
                                    </span>
                                </div>
                                <p className="text-xs text-slate-500 leading-relaxed max-w-md">
                                    Cổng thông tin và chia sẻ tài liệu học tập có kiểm duyệt dành riêng cho cộng đồng sinh viên và giảng viên AGU.
                                </p>
                                <p className="mt-2 text-xs text-slate-400">
                                    Số 18 Ung Văn Khiêm, Phường Đông Xuyên, TP. Long Xuyên, Tỉnh An Giang.
                                </p>
                            </div>

                            <div>
                                <h5 className="font-semibold text-slate-800 text-xs mb-3">
                                    Liên kết nhanh
                                </h5>
                                <ul className="space-y-2">
                                    <li>
                                        <Link href={route('faculties.index')} className="hover:text-slate-900 transition-colors">
                                            Danh mục Khoa đào tạo
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('document-requests.index')} className="hover:text-slate-900 transition-colors">
                                            Yêu cầu & Săn thưởng Bounty
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('courses.index')} className="hover:text-slate-900 transition-colors">
                                            Tra cứu & Đánh giá môn học
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('alumni.register')} className="hover:text-slate-900 transition-colors">
                                            Đăng ký Cựu sinh viên AGU
                                        </Link>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h5 className="font-semibold text-slate-800 text-xs mb-3">
                                    Quy định & Hỗ trợ
                                </h5>
                                <ul className="space-y-2 text-slate-400">
                                    <li>Quy chuẩn Liêm chính Học thuật</li>
                                    <li>Chính sách Bảo mật & Dữ liệu cá nhân</li>
                                    <li>Cơ chế Ví điểm & Giải ngân thưởng</li>
                                    <li>Liên hệ Ban Thư viện & Hỗ trợ sinh viên</li>
                                </ul>
                            </div>
                        </div>

                        <div className="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-400">
                            <div>
                                © {new Date().getFullYear()} AGU Academic Hub. Bản quyền thuộc Trường Đại học An Giang.
                            </div>
                            <div>
                                Nền tảng phát triển trên Laravel v{laravelVersion} (PHP v{phpVersion})
                            </div>
                        </div>
                    </div>
                </footer>

                {/* Modal: Giới thiệu Cơ Chế Ví Điểm Học Thuật (Dành cho Khách chưa đăng nhập) */}
                {showWalletModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
                        <div
                            className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl animate-in fade-in zoom-in-95 duration-150"
                            role="dialog"
                            aria-modal="true"
                        >
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div className="flex items-center gap-2 text-slate-900 font-semibold text-sm">
                                    <Coins className="h-4 w-4 text-brand-primary" />
                                    <span>Cơ Chế Ví Điểm Học Thuật AGU</span>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setShowWalletModal(false)}
                                    className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <div className="mt-4 space-y-3 text-xs text-slate-600 leading-relaxed">
                                <div className="rounded-lg bg-slate-50 p-3 border border-slate-100">
                                    <div className="font-semibold text-slate-800 mb-1">Cách thức nhận điểm:</div>
                                    <ul className="list-disc list-inside space-y-1 text-slate-600">
                                        <li>Tải lên tài liệu hữu ích (+ điểm khi tài liệu được duyệt).</li>
                                        <li>Phản hồi tài liệu cho các yêu cầu Bounty đang mở.</li>
                                        <li>Nhận thưởng điểm cọc sau khi người yêu cầu xác nhận.</li>
                                    </ul>
                                </div>

                                <div className="rounded-lg bg-slate-50 p-3 border border-slate-100">
                                    <div className="font-semibold text-slate-800 mb-1">Quyền lợi điểm thưởng:</div>
                                    <p>
                                        Điểm học thuật dùng để đặt hàng tài liệu hiếm, nâng cao chỉ số uy tín cộng đồng và đổi các quyền lợi học tập được công nhận.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-6 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowWalletModal(false)}
                                    className="rounded-lg px-3.5 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors"
                                >
                                    Đóng
                                </button>
                                <Link
                                    href={route('login')}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-brand-primary px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-brand-primary/95 transition-colors"
                                >
                                    <Lock className="h-3.5 w-3.5" />
                                    <span>Đăng nhập để xem ví</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
