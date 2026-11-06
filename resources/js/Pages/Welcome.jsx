import { Head, Link } from '@inertiajs/react';
import {
    GraduationCap,
    BookOpen,
    Award,
    Star,
    ShieldCheck,
    Search,
    ArrowRight,
    Coins,
    Users,
    FileText,
    CheckCircle2,
    Sparkles,
} from 'lucide-react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="AGU Academic Hub — Nền tảng Tri thức & Cộng đồng Sinh viên An Giang" />

            <div className="min-h-screen bg-surface-page text-text-primary flex flex-col selection:bg-brand-primary selection:text-white">
                {/* Top Navigation */}
                <header className="sticky top-0 z-50 border-b border-border-default bg-surface-default/95 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                        <Link href={route('home')} className="flex items-center gap-3 group">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary text-white shadow-sm transition group-hover:scale-105">
                                <GraduationCap className="h-6 w-6" />
                            </div>
                            <div>
                                <span className="block text-base font-bold tracking-tight text-brand-primary">
                                    AGU Academic Hub
                                </span>
                                <span className="block text-xs font-medium text-text-secondary">
                                    Đại học An Giang — ĐHQG-HCM
                                </span>
                            </div>
                        </Link>

                        <nav className="flex items-center gap-3">
                            <Link
                                href={route('faculties.index')}
                                className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-text-secondary hover:text-brand-primary transition rounded-lg hover:bg-slate-50"
                            >
                                <BookOpen className="h-4 w-4" />
                                Khoa & Môn học
                            </Link>
                            <Link
                                href={route('document-requests.index')}
                                className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-text-secondary hover:text-brand-primary transition rounded-lg hover:bg-slate-50"
                            >
                                <Award className="h-4 w-4" />
                                Săn thưởng Bounty
                            </Link>

                            {auth?.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-primary/90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2"
                                >
                                    <span>Vào Bảng Điều Khiển</span>
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={route('login')}
                                        className="rounded-lg px-3.5 py-2 text-sm font-medium text-brand-primary hover:bg-slate-100 transition"
                                    >
                                        Đăng nhập
                                    </Link>
                                    <Link
                                        href={route('alumni.register')}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-brand-secondary px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-secondary/90 transition"
                                    >
                                        <Users className="h-4 w-4" />
                                        <span>Cựu sinh viên</span>
                                    </Link>
                                </div>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <main className="flex-1">
                    <section className="relative overflow-hidden border-b border-border-default bg-gradient-to-b from-blue-50/50 via-white to-surface-page py-16 sm:py-24">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent pointer-events-none" />

                        <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                            {/* Pill Badge */}
                            <div className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/80 px-4 py-1.5 text-xs font-semibold text-brand-primary shadow-xs mb-6">
                                <Sparkles className="h-3.5 w-3.5 text-brand-secondary animate-pulse" />
                                <span>Cổng Tri thức & Học thuật Dành riêng cho AGUers</span>
                            </div>

                            <h1 className="mx-auto max-w-4xl text-3xl font-extrabold tracking-tight text-text-primary sm:text-5xl sm:leading-tight">
                                Chia sẻ Tài liệu, Gắn kết Tri thức & Nâng tầm Học thuật
                            </h1>

                            <p className="mx-auto mt-6 max-w-2xl text-base sm:text-lg text-text-secondary leading-relaxed">
                                Nền tảng số hóa học liệu chuyên ngành Trường Đại học An Giang. Tra cứu đề cương, bài giảng, đề thi đã kiểm duyệt và kết nối cộng đồng Sinh viên — Cựu sinh viên qua cơ chế Bounty minh bạch.
                            </p>

                            {/* CTAs */}
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                                <Link
                                    href={route('faculties.index')}
                                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-primary px-6 py-3.5 text-base font-semibold text-white shadow-md shadow-brand-primary/20 hover:bg-brand-primary/95 transition group"
                                >
                                    <Search className="h-5 w-5" />
                                    <span>Khám phá Kho Môn học</span>
                                    <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                                </Link>

                                {auth?.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="inline-flex items-center justify-center gap-2 rounded-xl border border-border-default bg-surface-default px-6 py-3.5 text-base font-semibold text-text-primary shadow-xs hover:bg-slate-50 transition"
                                    >
                                        <span>Bảng điều khiển cá nhân</span>
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center justify-center gap-2 rounded-xl border border-border-default bg-surface-default px-6 py-3.5 text-base font-semibold text-text-primary shadow-xs hover:bg-slate-50 transition"
                                    >
                                        <GraduationCap className="h-5 w-5 text-brand-primary" />
                                        <span>Đăng nhập Cổng Sinh viên</span>
                                    </Link>
                                )}
                            </div>

                            {/* Trust Badges */}
                            <div className="mt-12 flex flex-wrap items-center justify-center gap-6 text-xs sm:text-sm text-text-secondary">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-brand-secondary" />
                                    <span>Kiểm duyệt liêm chính học thuật</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-brand-secondary" />
                                    <span>Phân loại theo Chương trình Đào tạo</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-brand-secondary" />
                                    <span>Đánh giá môn học văn minh & bảo mật</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Features Grid */}
                    <section className="py-16 sm:py-20 bg-surface-default">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="text-center max-w-3xl mx-auto mb-14">
                                <h2 className="text-xs font-bold uppercase tracking-wider text-brand-secondary">
                                    Tính năng Nổi bật
                                </h2>
                                <p className="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-text-primary">
                                    Hệ sinh thái Tri thức Toàn diện cho AGU
                                </p>
                                <p className="mt-3 text-text-secondary">
                                    Được thiết kế tối ưu hóa cho trải nghiệm học tập, nghiên cứu và đóng góp tài liệu của sinh viên.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                {/* Card 1 */}
                                <div className="rounded-2xl border border-border-default bg-surface-page p-6 transition hover:shadow-md hover:border-brand-primary/30 flex flex-col justify-between">
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-brand-primary mb-5">
                                            <FileText className="h-6 w-6" />
                                        </div>
                                        <h3 className="text-lg font-bold text-text-primary">
                                            Kho Học Liệu Số
                                        </h3>
                                        <p className="mt-2 text-sm text-text-secondary leading-relaxed">
                                            Đầy đủ đề cương môn học, bài giảng, slide thuyết trình và đề thi các học kỳ trước được tổ chức khoa học theo từng Khoa và Chuyên ngành.
                                        </p>
                                    </div>
                                    <div className="mt-4 pt-4 border-t border-border-default text-xs font-semibold text-brand-primary">
                                        Xem trước PDF trực tuyến →
                                    </div>
                                </div>

                                {/* Card 2 */}
                                <div className="rounded-2xl border border-border-default bg-surface-page p-6 transition hover:shadow-md hover:border-brand-secondary/30 flex flex-col justify-between">
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-brand-secondary mb-5">
                                            <Award className="h-6 w-6" />
                                        </div>
                                        <h3 className="text-lg font-bold text-text-primary">
                                            Săn Thưởng Bounty
                                        </h3>
                                        <p className="mt-2 text-sm text-text-secondary leading-relaxed">
                                            Đặt hàng tài liệu hiếm bằng điểm cọc. Sinh viên khóa trên và cựu sinh viên có thể phản hồi đóng góp tài liệu để nhận giải ngân phần thưởng.
                                        </p>
                                    </div>
                                    <div className="mt-4 pt-4 border-t border-border-default text-xs font-semibold text-brand-secondary">
                                        Cộng điểm ví & Uy tín →
                                    </div>
                                </div>

                                {/* Card 3 */}
                                <div className="rounded-2xl border border-border-default bg-surface-page p-6 transition hover:shadow-md hover:border-amber-500/30 flex flex-col justify-between">
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 mb-5">
                                            <Star className="h-6 w-6" />
                                        </div>
                                        <h3 className="text-lg font-bold text-text-primary">
                                            Đánh Giá Môn Học
                                        </h3>
                                        <p className="mt-2 text-sm text-text-secondary leading-relaxed">
                                            Chia sẻ đánh giá khách quan về độ khó, tính thực tiễn và khối lượng bài tập. Bộ lọc từ ngữ tự động bảo đảm văn hóa học đường văn minh.
                                        </p>
                                    </div>
                                    <div className="mt-4 pt-4 border-t border-border-default text-xs font-semibold text-amber-600">
                                        Bình chọn nhận xét hữu ích →
                                    </div>
                                </div>

                                {/* Card 4 */}
                                <div className="rounded-2xl border border-border-default bg-surface-page p-6 transition hover:shadow-md hover:border-indigo-500/30 flex flex-col justify-between">
                                    <div>
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 mb-5">
                                            <Coins className="h-6 w-6" />
                                        </div>
                                        <h3 className="text-lg font-bold text-text-primary">
                                            Ví Điểm Học Thuật
                                        </h3>
                                        <p className="mt-2 text-sm text-text-secondary leading-relaxed">
                                            Hệ thống điểm ví ghi nhận mọi nỗ lực đóng góp tài liệu, lượt thích và chia sẻ giá trị trong cộng đồng Đại học An Giang.
                                        </p>
                                    </div>
                                    <div className="mt-4 pt-4 border-t border-border-default text-xs font-semibold text-indigo-600">
                                        Minh bạch biến động điểm →
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Academic Integrity & Faculty Quick Stats */}
                    <section className="border-y border-border-default bg-slate-50 py-16">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                                <div className="p-6 rounded-2xl bg-white border border-border-default shadow-xs">
                                    <div className="text-4xl font-extrabold text-brand-primary">6+</div>
                                    <div className="mt-2 text-sm font-semibold text-text-primary">Khoa Đào tạo Chuyên ngành</div>
                                    <p className="mt-1 text-xs text-text-secondary">CNTT, Kinh tế, Sư phạm, Nông nghiệp, KHXH&NV, Ngoại ngữ</p>
                                </div>
                                <div className="p-6 rounded-2xl bg-white border border-border-default shadow-xs">
                                    <div className="text-4xl font-extrabold text-brand-secondary">100%</div>
                                    <div className="mt-2 text-sm font-semibold text-text-primary">Kiểm soát Chất lượng</div>
                                    <p className="mt-1 text-xs text-text-secondary">Chống tải trùng lặp mã băm SHA-256 và quy trình hậu kiểm nghiêm ngặt</p>
                                </div>
                                <div className="p-6 rounded-2xl bg-white border border-border-default shadow-xs">
                                    <div className="text-4xl font-extrabold text-indigo-600">2-Way</div>
                                    <div className="mt-2 text-sm font-semibold text-text-primary">Kết nối Thế hệ AGU</div>
                                    <p className="mt-1 text-xs text-text-secondary">Sinh viên hiện hành & Cựu sinh viên cùng xây dựng kho tri thức chung</p>
                                </div>
                            </div>

                            {/* Notice callout */}
                            <div className="mt-12 rounded-2xl border border-blue-200 bg-blue-50/70 p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-6 text-center sm:text-left">
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-primary text-white shadow-sm">
                                    <ShieldCheck className="h-8 w-8" />
                                </div>
                                <div className="flex-1">
                                    <h4 className="text-base font-bold text-text-primary">
                                        Cam Kết Liêm Chính Học Thuật
                                    </h4>
                                    <p className="mt-1 text-sm text-text-secondary leading-relaxed">
                                        AGU Academic Hub nghiêm cấm việc phát tán tài liệu thi bí mật chưa công bố, sao chép gian lận hoặc vi phạm bản quyền tác giả. Mọi tài liệu trước khi xuất bản đều được giám sát bởi Ban Kiểm duyệt nhà trường.
                                    </p>
                                </div>
                                <div className="shrink-0">
                                    <Link
                                        href={route('faculties.index')}
                                        className="inline-flex items-center gap-2 rounded-xl bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary/90 transition shadow-sm"
                                    >
                                        Bắt đầu tra cứu
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="border-t border-border-default bg-surface-default py-12 text-sm text-text-secondary">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                            <div className="md:col-span-2">
                                <div className="flex items-center gap-2 mb-3">
                                    <GraduationCap className="h-6 w-6 text-brand-primary" />
                                    <span className="font-bold text-text-primary text-base">
                                        Trường Đại học An Giang — ĐHQG-HCM
                                    </span>
                                </div>
                                <p className="text-xs text-text-secondary leading-relaxed max-w-md">
                                    AGU Academic Hub — Cổng chia sẻ tri thức và tài liệu học tập chính thống dành riêng cho cộng đồng Sinh viên và Cựu sinh viên Đại học An Giang.
                                </p>
                                <p className="mt-3 text-xs text-slate-500">
                                    Địa chỉ: Số 18 Ung Văn Khiêm, Phường Đông Xuyên, Thành phố Long Xuyên, Tỉnh An Giang.
                                </p>
                            </div>

                            <div>
                                <h5 className="font-semibold text-text-primary text-xs uppercase tracking-wider mb-3">
                                    Điều Hướng Nhanh
                                </h5>
                                <ul className="space-y-2 text-xs">
                                    <li>
                                        <Link href={route('faculties.index')} className="hover:text-brand-primary transition">
                                            Danh mục Khoa đào tạo
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('document-requests.index')} className="hover:text-brand-primary transition">
                                            Săn thưởng Bounty
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('login')} className="hover:text-brand-primary transition">
                                            Cổng Đăng nhập Sinh viên
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('alumni.register')} className="hover:text-brand-primary transition">
                                            Đăng ký Cựu sinh viên
                                        </Link>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h5 className="font-semibold text-text-primary text-xs uppercase tracking-wider mb-3">
                                    Hỗ Trợ & Học Thuật
                                </h5>
                                <ul className="space-y-2 text-xs text-slate-500">
                                    <li>Quy chuẩn Liêm chính Học thuật</li>
                                    <li>Chính sách Bảo mật Thông tin</li>
                                    <li>Quy chế Đóng góp Tài liệu & Ví điểm</li>
                                    <li>Liên hệ Ban Quản trị Cổng Thông tin</li>
                                </ul>
                            </div>
                        </div>

                        <div className="pt-8 border-t border-border-default flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                            <div>
                                © {new Date().getFullYear()} AGU Academic Hub. Bản quyền thuộc về Trường Đại học An Giang.
                            </div>
                            <div>
                                Nền tảng phát triển trên Laravel v{laravelVersion} (PHP v{phpVersion})
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
