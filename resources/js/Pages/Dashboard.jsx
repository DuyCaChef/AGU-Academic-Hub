import { Head, Link, usePage } from "@inertiajs/react";
import {
    BookOpen,
    Award,
    Upload,
    Search,
    FileText,
    ArrowRight,
    Clock3,
    GraduationCap,
    Building2,
    Download,
    ShieldCheck,
    Compass,
    Coins,
} from "lucide-react";
import StudentLayout from "@/Layouts/StudentLayout";

const quickLinks = [
    {
        title: "Tìm môn học",
        description: "Tra cứu giáo trình, đề cương chi tiết và danh mục mã học phần.",
        icon: Search,
        href: "courses.index",
    },
    {
        title: "Săn thưởng tài liệu",
        description: "Đăng tải tài liệu theo yêu cầu để nhận Bounty điểm ví từ cộng đồng.",
        icon: Coins,
        href: "document-requests.index",
        badge: "Bounty",
    },
    {
        title: "Duyệt danh sách khoa",
        description: "Xem các khoa, bộ môn và chương trình đào tạo hiện tại của AGU.",
        icon: Building2,
        href: "faculties.index",
    },
    {
        title: "Đăng tải tài liệu",
        description: "Chia sẻ đề thi, slide bài giảng hoặc tài liệu tham khảo cho các khóa sau.",
        icon: Upload,
        href: "documents.create",
    },
];

const gettingStarted = [
    {
        step: "01",
        title: "Tìm môn học",
        description: "Tra cứu theo mã môn hoặc tên khoa để truy cập đề cương học phần và danh mục giáo trình tương ứng.",
    },
    {
        step: "02",
        title: "Khám phá tài liệu",
        description: "Tải về đề thi mẫu, slide bài giảng tiêu chuẩn và ngân hàng bài tập thực hành được kiểm duyệt kỹ càng.",
    },
    {
        step: "03",
        title: "Chia sẻ cùng cộng đồng",
        description: "Đóng góp tài liệu học tập để tích lũy điểm uy tín và nhận thưởng Bounty từ các bạn đồng môn.",
    },
];

function SectionHeader({ title, description, action }) {
    return (
        <div className="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 className="text-base font-semibold text-slate-900 tracking-tight">
                    {title}
                </h2>
                {description && (
                    <p className="text-xs text-slate-500 mt-0.5">
                        {description}
                    </p>
                )}
            </div>
            {action && <div>{action}</div>}
        </div>
    );
}

function StatCard({ label, value, description, icon: Icon, tag }) {
    return (
        <article className="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs transition-colors hover:border-slate-300">
            <div className="flex items-center justify-between gap-3">
                <span className="text-xs font-medium text-slate-500">
                    {label}
                </span>
                {tag && (
                    <span className="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 border border-slate-200/60">
                        {tag}
                    </span>
                )}
            </div>

            <div className="mt-3 flex items-baseline gap-2">
                <span className="font-mono text-3xl font-semibold tracking-tight text-slate-900 tabular-nums">
                    {value}
                </span>
            </div>

            <div className="mt-3 flex items-center gap-1.5 text-xs text-slate-500 border-t border-slate-100 pt-3">
                <Icon size={14} className="shrink-0 text-slate-400" />
                <span className="truncate">{description}</span>
            </div>
        </article>
    );
}

export default function Dashboard() {
    const { auth, stats = {}, recentDocuments = [] } = usePage().props;
    const user = auth?.user;

    const isAdmin = Boolean(user?.la_quan_tri || user?.vai_tro === "QUAN_TRI_VIEN");
    const isModerator = Boolean(user?.la_kiem_duyet || user?.vai_tro === "KIEM_DUYET_VIEN");
    const isAlumni = user?.vai_tro === "CUU_SINH_VIEN";
    const isStaff = isAdmin || isModerator;

    const displayName = user?.ho_ten || user?.name || (isStaff ? "Quản trị viên" : "sinh viên");
    const trustScore = stats.trustScore ?? user?.diem_uy_tin ?? "—";

    let roleTitle = "Sinh viên chính quy";
    if (isAdmin) {
        roleTitle = user?.cap_quan_tri === "TOI_CAO" ? "Super Admin" : "Quản trị viên";
    } else if (isModerator) {
        roleTitle = "Kiểm duyệt viên";
    } else if (isAlumni) {
        roleTitle = "Cựu sinh viên";
    }

    const effectiveQuickLinks = [
        {
            title: "Tìm môn học",
            description: "Tra cứu giáo trình, đề cương chi tiết và danh mục mã học phần.",
            icon: Search,
            href: "courses.index",
        },
        isStaff ? {
            title: "Duyệt giải ngân Bounty",
            description: "Xem và phê duyệt các yêu cầu giải ngân điểm thưởng tài liệu.",
            icon: Coins,
            href: "admin.bounties.payout-queue",
            badge: "Admin",
        } : {
            title: "Săn thưởng tài liệu",
            description: "Đăng tải tài liệu theo yêu cầu để nhận Bounty điểm ví từ cộng đồng.",
            icon: Coins,
            href: "document-requests.index",
            badge: "Bounty",
        },
        {
            title: "Duyệt danh sách khoa",
            description: "Xem các khoa, bộ môn và chương trình đào tạo hiện tại của AGU.",
            icon: Building2,
            href: "faculties.index",
        },
        isStaff ? {
            title: "Bảng điều khiển Quản trị",
            description: "Quản lý toàn bộ người dùng, danh mục đào tạo và kiểm duyệt.",
            icon: ShieldCheck,
            href: "admin.dashboard",
            badge: "Admin",
        } : {
            title: "Đăng tải tài liệu",
            description: "Chia sẻ đề thi, slide bài giảng hoặc tài liệu tham khảo cho các khóa sau.",
            icon: Upload,
            href: "documents.create",
        },
    ];

    return (
        <StudentLayout title="Tổng quan">
            <Head title="Tổng quan học tập" />

            <div className="space-y-8">
                {/* 1. Hero banner: Asymmetric split with Academic Passport */}
                <section className="relative overflow-hidden rounded-2xl bg-[#005BAA] text-white shadow-xs">
                    {/* Subtle academic architectural background grid */}
                    <div className="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />

                    <div className="relative grid items-center gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_300px]">
                        {/* Cột trái: Tiêu đề & Hành động */}
                        <div className="space-y-4">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-blue-100 backdrop-blur-xs border border-white/10">
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                                <span>{isStaff ? "Không gian Điều hành & Tra cứu" : "Cổng học tập & Tra cứu tài liệu"}</span>
                            </div>

                            <div className="space-y-1">
                                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl text-white">
                                    Chào mừng trở lại, {displayName}
                                </h1>
                                <p className="text-sm leading-relaxed text-blue-100 max-w-xl">
                                    {isStaff
                                        ? "Hệ thống quản lý học liệu và danh mục học thuật Trường Đại học An Giang. Truy cập nhanh vào Bảng điều khiển Quản trị hoặc tra cứu tài nguyên đào tạo."
                                        : "Kho lưu trữ số chính thức của Đại học An Giang. Tra cứu đề cương, tải tài liệu đã kiểm duyệt và cùng cộng đồng học thuật chia sẻ tri thức."
                                    }
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-3 pt-2">
                                {isStaff && (
                                    <Link
                                        href={route("admin.dashboard")}
                                        className="inline-flex items-center gap-2 rounded-lg bg-amber-400 hover:bg-amber-300 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-xs transition-colors"
                                    >
                                        <ShieldCheck size={16} />
                                        <span>Bảng điều khiển Quản trị</span>
                                    </Link>
                                )}

                                <Link
                                    href={route("courses.index")}
                                    className={`inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-xs font-semibold shadow-xs transition-colors ${
                                        isStaff
                                            ? "bg-white/10 text-white border border-white/20 hover:bg-white/20"
                                            : "bg-white text-[#005BAA] hover:bg-blue-50"
                                    }`}
                                >
                                    <BookOpen size={16} />
                                    <span>Khám phá môn học</span>
                                </Link>

                                {!isStaff && (
                                    <Link
                                        href={route("documents.create")}
                                        className="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs font-semibold text-white border border-white/20 transition-colors hover:bg-white/20"
                                    >
                                        <Upload size={16} />
                                        <span>Đăng tải tài liệu</span>
                                    </Link>
                                )}
                            </div>
                        </div>

                        {/* Cột phải: Thẻ Hồ sơ Học thuật / Quản trị viên */}
                        <div className="rounded-xl border border-white/15 bg-white/10 p-5 backdrop-blur-xs text-white space-y-4">
                            <div className="flex items-start justify-between gap-2 border-b border-white/10 pb-3">
                                <div className="min-w-0 flex-1">
                                    <p className="text-[11px] font-medium text-blue-200">
                                        {isStaff ? "HỒ SƠ QUẢN TRỊ VIÊN" : "HỒ SƠ HỌC THUẬT"}
                                    </p>
                                    <p className="text-sm font-semibold tracking-tight text-white mt-0.5 truncate">
                                        {isStaff ? (user?.email || "Quản trị viên AGU") : (user?.mssv || "Chưa gán MSSV")}
                                    </p>
                                </div>
                                <span className="rounded-md bg-white/15 px-2 py-0.5 text-[10px] font-medium text-blue-100 border border-white/10 shrink-0">
                                    {roleTitle}
                                </span>
                            </div>

                            <div className="space-y-1.5 text-xs text-blue-100">
                                <div className="flex items-center justify-between">
                                    <span className="text-blue-200">Đơn vị:</span>
                                    <span className="font-medium text-white truncate max-w-[170px] text-right">
                                        {user?.khoa?.ten_khoa || (isStaff ? "Ban Quản Trị AGU Hub" : "Trường Đại học An Giang")}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-blue-200">{isStaff ? "Quyền hạn:" : "Độ tin cậy:"}</span>
                                    <span className="font-medium text-white flex items-center gap-1">
                                        <ShieldCheck size={13} className="text-emerald-300" />
                                        {isStaff ? (user?.cap_quan_tri === "TOI_CAO" ? "Toàn quyền tối cao" : "Quản trị hệ thống") : `${trustScore} AGU Trust`}
                                    </span>
                                </div>
                            </div>

                            {/* Nút lối tắt dưới thẻ hồ sơ */}
                            {isStaff ? (
                                <Link
                                    href={route("admin.dashboard")}
                                    className="flex items-center justify-between rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 px-3 py-2 text-xs font-bold shadow-xs transition-colors"
                                    title="Đến Bảng điều khiển Quản trị"
                                >
                                    <span className="flex items-center gap-1.5">
                                        <ShieldCheck size={14} className="text-slate-950" />
                                        Bảng Quản trị:
                                    </span>
                                    <span className="text-xs">
                                        Admin Console &rarr;
                                    </span>
                                </Link>
                            ) : (
                                <Link
                                    href={typeof route === "function" && route().has("wallet.index") ? route("wallet.index") : "/vi-diem"}
                                    className="flex items-center justify-between rounded-lg bg-black/20 hover:bg-black/30 px-3 py-2 text-xs font-medium border border-white/10 transition-colors"
                                    title="Xem chi tiết ví điểm thưởng"
                                >
                                    <span className="flex items-center gap-1.5 text-blue-100">
                                        <Coins size={14} className="text-amber-300" />
                                        Ví khả dụng:
                                    </span>
                                    <span className="font-mono font-semibold text-amber-300 text-xs">
                                        {user?.vi_diem?.diem_kha_dung || 0} pts &rarr;
                                    </span>
                                </Link>
                            )}
                        </div>
                    </div>
                </section>

                {/* 2. Thống kê học thuật (3 thẻ chỉ số) */}
                <section>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard
                            label="Môn học trong hệ thống"
                            value={stats.totalCourses ?? 0}
                            description="Chương trình đào tạo tín chỉ AGU"
                            icon={BookOpen}
                            tag="Học kỳ 1 • 2024"
                        />

                        <StatCard
                            label="Tài liệu đã đóng góp"
                            value={stats.myDocuments ?? 0}
                            description="Tài liệu bạn đã đăng tải và duyệt"
                            icon={FileText}
                            tag="Kho lưu trữ"
                        />

                        <StatCard
                            label="Điểm uy tín tích lũy"
                            value={trustScore}
                            description="Căn cứ từ đánh giá cộng đồng"
                            icon={Award}
                            tag="AGU Trust"
                        />
                    </div>
                </section>

                {/* 3. Lối tắt thao tác (Quick Access) */}
                <section>
                    <SectionHeader
                        title="Truy cập nhanh"
                        description="Lối tắt đến các chức năng học thuật chính trên nền tảng"
                    />

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {effectiveQuickLinks.map((item) => {
                            const Icon = item.icon;

                            return (
                                <Link
                                    key={item.title}
                                    href={typeof route === "function" && route().has(item.href) ? route(item.href) : "#"}
                                    className="group flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs transition-colors hover:border-slate-300 hover:shadow-xs min-h-[120px]"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors group-hover:bg-blue-50 group-hover:text-[#005BAA]">
                                            <Icon size={18} />
                                        </div>

                                        {item.badge && (
                                            <span className="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 border border-amber-200">
                                                {item.badge}
                                            </span>
                                        )}
                                    </div>

                                    <div className="mt-3">
                                        <div className="flex items-center justify-between gap-1">
                                            <h3 className="text-xs font-semibold text-slate-900 group-hover:text-[#005BAA] transition-colors">
                                                {item.title}
                                            </h3>
                                            <ArrowRight
                                                size={14}
                                                className="text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#005BAA]"
                                            />
                                        </div>
                                        <p className="mt-1 text-[11px] leading-relaxed text-slate-500 line-clamp-2">
                                            {item.description}
                                        </p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </section>

                {/* 4. Tài liệu học tập mới cập nhật */}
                <section>
                    <SectionHeader
                        title="Tài liệu học tập mới"
                        description="Các tài liệu vừa được ban kiểm duyệt thông qua"
                        action={
                            <Link
                                href={typeof route === "function" && route().has("documents.index") ? route("documents.index") : "/tai-lieu"}
                                className="inline-flex items-center gap-1 text-xs font-semibold text-[#005BAA] hover:underline"
                            >
                                <span>Xem toàn bộ tài liệu</span>
                                <ArrowRight size={14} />
                            </Link>
                        }
                    />

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {recentDocuments && recentDocuments.length > 0 ? (
                            recentDocuments.map((doc) => {
                                const fileType = (doc.dinh_dang_tep || "FILE").toUpperCase();
                                const courseLabel = [
                                    doc.mon_hoc?.ma_hoc_phan,
                                    doc.mon_hoc?.ten_mon_hoc,
                                ]
                                    .filter(Boolean)
                                    .join(" • ") || "Môn học chung";

                                const createdDate = doc.created_at
                                    ? new Date(doc.created_at).toLocaleDateString("vi-VN")
                                    : "Vừa xong";

                                return (
                                    <article
                                        key={doc.ma_tai_lieu}
                                        className="group flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs transition-colors hover:border-slate-300"
                                    >
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="font-mono text-[10px] font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200/80">
                                                    {fileType}
                                                </span>

                                                <span className="truncate max-w-[70%] font-mono text-[10px] font-medium text-[#005BAA] bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                                                    {courseLabel}
                                                </span>
                                            </div>

                                            <h3 className="text-xs font-semibold leading-relaxed text-slate-900 group-hover:text-[#005BAA] transition-colors line-clamp-2 min-h-[2.5rem]">
                                                <Link
                                                    href={typeof route === "function" && route().has("documents.show") ? route("documents.show", doc.ma_tai_lieu) : `/tai-lieu/${doc.ma_tai_lieu}`}
                                                    title={doc.tieu_de}
                                                >
                                                    {doc.tieu_de}
                                                </Link>
                                            </h3>
                                        </div>

                                        <div className="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-1.5 text-[11px] text-emerald-700">
                                                    <ShieldCheck size={13} className="shrink-0" />
                                                    <span>Đã kiểm duyệt</span>
                                                </div>
                                                <p className="mt-0.5 text-[10px] text-slate-400">
                                                    {createdDate} • {doc.luot_tai ?? 0} lượt tải
                                                </p>
                                            </div>

                                            <a
                                                href={typeof route === "function" && route().has("documents.download") ? route("documents.download", doc.ma_tai_lieu) : `/tai-lieu/${doc.ma_tai_lieu}/tai-ve`}
                                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700 hover:bg-[#005BAA] hover:text-white transition-colors"
                                                title="Tải tệp về máy"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <Download size={15} />
                                            </a>
                                        </div>
                                    </article>
                                );
                            })
                        ) : (
                            <div className="col-span-full rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center">
                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 mb-3">
                                    <FileText size={24} />
                                </div>
                                <h3 className="text-xs font-semibold text-slate-900">
                                    Chưa có tài liệu mới
                                </h3>
                                <p className="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                                    Tài liệu sau khi được duyệt sẽ hiển thị tại đây. Bạn có thể là người đầu tiên chia sẻ tài liệu học tập!
                                </p>
                                <div className="mt-4">
                                    <Link
                                        href={typeof route === "function" && route().has("documents.create") ? route("documents.create") : "/tai-lieu/dang-tai"}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-[#005BAA] px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <Upload size={14} />
                                        <span>Đăng tài liệu ngay</span>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                {/* 5. Quy trình 3 bước (Getting Started - Academic Onboarding) */}
                <section className="rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs">
                    <div className="mb-4 flex items-center gap-2.5">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <Compass size={18} />
                        </div>
                        <div>
                            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-900">
                                Bắt đầu với AGU Academic Hub
                            </h2>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Ba bước đơn giản để khai thác tối đa cổng tài nguyên học thuật
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3 pt-2">
                        {gettingStarted.map((item) => (
                            <div
                                key={item.step}
                                className="rounded-lg border border-slate-100 bg-slate-50/70 p-4 transition-colors hover:bg-slate-50"
                            >
                                <span className="font-mono text-xs font-semibold text-slate-400">
                                    {item.step}
                                </span>
                                <h3 className="mt-1 text-xs font-semibold text-slate-900">
                                    {item.title}
                                </h3>
                                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                                    {item.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </StudentLayout>
    );
}
