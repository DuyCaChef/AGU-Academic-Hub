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
    BadgeCheck,
    Compass,
    Users,
    ShieldCheck,
} from "lucide-react";
import StudentLayout from "@/Layouts/StudentLayout";
import { Description } from '@headlessui/react';
const quickLinks = [
    {
        title: "Tìm môn học",
        description:
            "Tra cứu giáo trình, đề cương chi tiết và danh mục mã học phần.",
        icon: Search,
        href: "courses.index",
        iconColor: "text-blue-800",
    },
    {
        title: "Duyệt danh sách khoa",
        description:
            "Xem tất cả các khoa, bộ môn và chương trình đào tạo tại AGU.",
        icon: Building2,
        href: "faculties.index",
        iconColor: "text-teal-700",
    },
    {
        title: "Đăng tài liệu",
        description:
            "Tải lên đề thi, slide bài giảng hoặc bài tập hỗ trợ các khóa sau.",
        icon: Upload,
        href: "documents.create",
        iconColor: "text-blue-800",
    },
]
const sampleDocuments = [
    {
        type: "PDF",
        typeColor: "bg-red-100 text-red-700",
        course: "COS101 • Nhập môn CNTT",
        title: "Đề thi cuối kỳ COS101 – Kèm ma trận đề và đáp án chi tiết",
        date: "12/10/2024",
        source: "Đã kiểm duyệt",
        sourceIcon: BadgeCheck,
        sourceColor: "text-teal-700",
    },
    {
        type: "PPTX",
        typeColor: "bg-orange-100 text-orange-800",
        course: "COS102 • Lập trình C/C++",
        title: "Slide bài giảng Chuẩn cấu trúc dữ liệu mảng và con trỏ",
        date: "08/10/2024",
        source: "Giảng viên chia sẻ",
        sourceIcon: GraduationCap,
        sourceColor: "text-blue-700",
    },
    {
        type: "DOCX",
        typeColor: "bg-indigo-100 text-indigo-700",
        course: "COS201 • CTDL & Giải thuật",
        title: "Tổng hợp bài tập thực hành cây nhị phân tìm kiếm và mã giả",
        date: "05/10/2024",
        source: "Bạn cán sự SV",
        sourceIcon: Users,
        sourceColor: "text-gray-500",
    },
];
const gettingStarted = [
    {
        number: "1",
        title: "Tìm môn học của bạn",
        description:
            "Tra cứu theo mã môn hoặc tên khoa để truy cập đề cương học phần và danh mục giáo trình tương ứng.",
        color: "bg-blue-100 text-blue-800",
    },
    {
        number: "2",
        title: "Khám phá tài liệu hữu ích",
        description:
            "Tải về đề thi mẫu, slide bài giảng tiêu chuẩn và ngân hàng bài tập thực hành được kiểm duyệt kỹ càng.",
        color: "bg-teal-200 text-teal-800",
    },
    {
        number: "3",
        title: "Chia sẻ cùng cộng đồng",
        description:
            "Đóng góp tài liệu học tập của bạn để tích lũy điểm uy tín và hỗ trợ các bạn đồng môn tại An Giang.",
        color: "bg-orange-200 text-orange-800",
    },
];
function SectionTitle({ children, action }) {
    return (
        <div className="mb-4 flex items-center justify-between gap-3">
            <h2 className="text-xl font-bold tracking-tight text-slate-900">
                {children}
            </h2>
            {action}
        </div>
    );
}
function StatCard({
    label,
    value,
    description,
    icon: Icon,
    iconColor,
    badge,
    badgeColor,
}) {
    return (
        <article className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
            <div className="flex items-center justify-between gap-3">
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100">
                    <Icon size={23} className={iconColor} />
                </div>

                {badge && (
                    <span
                        className={`rounded-full px-2.5 py-1 text-xs font-medium ${badgeColor}`}
                    >
                        {badge}
                    </span>
                )}
            </div>

            <p className="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-600">
                {label}
            </p>

            <p className="mt-3 text-3xl font-bold text-slate-900">
                {value}
            </p>

            <p className="mt-3 flex items-start gap-2 text-xs leading-5 text-slate-500">
                <Clock3 size={15} className="mt-0.5 shrink-0" />
                {description}
            </p>
        </article>
    );
}
// const stats = [
//     {
//         label: 'Môn học',
//         value: '-',
//         description: 'Khám phá kho môn học',
//         icon: BookOpen,
//         href: route('courses.index')
//     },
//     // {
//     //     label: 'Tài liệu đóng góp',
//     //     value: '-',
//     //     description: 'Chia sẽ kiến thức với cộng đồng',
//     //     icon: FileText,
//     //     href: route('documents.show')
//     // },
//     {
//         label: 'Điểm uy tín',
//         value: user?.diem_uy_tin ?? '—',
//         description: 'Danh hiệu và đóng góp',
//         icon: Award,
//         href: ''
//     }
// ]
export default function Dashboard() {
    const { auth } = usePage().props;
    const user = auth?.user;

    const displayName =
        user?.ho_ten || user?.name || "sinh viên";

    const trustScore = user?.diem_uy_tin ?? "—";
    return (
        <StudentLayout title="Tổng quan">
            <Head title="Tổng quan" />

            <div className="mx-auto max-w-[1600px] space-y-8 px-4 py-6 sm:px-6 lg:px-6">
                {/* 1. Welcome banner */}
                <section className="relative isolate overflow-hidden rounded-2xl bg-[#005BAA] p-6 text-white shadow-md sm:p-8">
                    {/* Decorative background */}
                    <div className="pointer-events-none absolute -right-8 top-1/2 -z-10 h-56 w-56 -translate-y-1/2 rotate-45 border-[3px] border-white/10 sm:right-12 sm:h-48 sm:w-48">
                        <div className="absolute inset-5 border-2 border-white/10" />
                    </div>

                    <div className="grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_280px]">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full bg-blue-400/30 px-3 py-1 text-[11px] font-bold tracking-widest text-blue-100">
                                <span className="h-1.5 w-1.5 rounded-full bg-teal-300" />
                                AGU ACADEMIC HUB
                            </div>

                            <h1 className="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                Xin chào, {displayName}!
                            </h1>

                            <p className="mt-3 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">
                                Khám phá tài liệu học tập, tìm kiếm môn học
                                và chia sẻ kiến thức cùng cộng đồng sinh viên
                                Đại học An Giang.
                            </p>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link
                                    href={route("courses.index")}
                                    className="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-[#005BAA] transition hover:bg-blue-50"
                                >
                                    <BookOpen size={17} />
                                    Khám phá môn học
                                </Link>

                                <Link
                                    href={route("documents.create")}
                                    className="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-inset ring-white/10 transition hover:bg-white/20"
                                >
                                    <Upload size={17} />
                                    Đăng tài liệu
                                </Link>
                            </div>
                        </div>

                        {/* Profile highlight */}
                        <div className="hidden rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm sm:block">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-300">
                                    <ShieldCheck
                                        size={27}
                                        className="text-teal-800"
                                    />
                                </div>

                                <div className="min-w-0">
                                    <p className="text-[11px] font-medium uppercase tracking-wide text-blue-100">
                                        Hồ sơ sinh viên
                                    </p>
                                    <p className="mt-1 font-bold leading-5 text-white">
                                        {user?.ma_sinh_vien || "AGU Student"}
                                    </p>
                                    <p className="mt-1 text-xs text-blue-100">
                                        Đại học An Giang
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 2. Statistics */}
                <section>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <StatCard
                            label="Môn học"
                            value="—"
                            description="Chưa liên kết học kỳ hiện tại"
                            icon={BookOpen}
                            iconColor="text-blue-800"
                            badge="Kỳ 1 • 2024"
                            badgeColor="bg-indigo-100 text-slate-500"
                        />

                        <StatCard
                            label="Tài liệu đã đóng góp"
                            value="—"
                            description="Dữ liệu đang đồng bộ hệ thống"
                            icon={FileText}
                            iconColor="text-teal-700"
                            badge="Kho lưu trữ"
                            badgeColor="bg-teal-50 text-teal-700"
                        />

                        <StatCard
                            label="Điểm uy tín"
                            value={trustScore}
                            description="Hệ thống ghi nhận đóng góp học thuật"
                            icon={Award}
                            iconColor="text-amber-800"
                            badge="AGU Trust"
                            badgeColor="bg-amber-50 text-amber-800"
                        />
                    </div>
                </section>

                {/* 3. Quick access */}
                <section>
                    <SectionTitle>
                        Truy cập nhanh
                    </SectionTitle>

                    <p className="-mt-2 mb-4 text-sm text-slate-600">
                        Lối tắt đến các tính năng học thuật thường dùng nhất
                        trên nền tảng.
                    </p>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {quickLinks.map((item) => {
                            const Icon = item.icon;

                            return (
                                <Link
                                    key={item.title}
                                    href={route(item.href)}
                                    className="group flex min-h-28 items-start gap-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                                >
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100">
                                        <Icon
                                            size={23}
                                            className={item.iconColor}
                                        />
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-2">
                                            <h3 className="font-bold text-slate-900">
                                                {item.title}
                                            </h3>
                                            <ArrowRight
                                                size={17}
                                                className="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-700"
                                            />
                                        </div>

                                        <p className="mt-1 text-xs leading-5 text-slate-600 sm:text-sm">
                                            {item.description}
                                        </p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </section>

                {/* 4. Recent learning materials */}
                <section>
                    <SectionTitle
                        action={
                            <Link
                                href={route("courses.index")}
                                className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-blue-800 hover:text-blue-600"
                            >
                                Khám phá kho học tập
                                <ArrowRight size={16} />
                            </Link>
                        }
                    >
                        <span className="flex flex-wrap items-center gap-2">
                            Tài liệu học tập mới
                            <span className="rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                Mẫu giao diện
                            </span>
                        </span>
                    </SectionTitle>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {sampleDocuments.map((doc) => {
                            const SourceIcon = doc.sourceIcon;

                            return (
                                <article
                                    key={doc.course}
                                    className="flex flex-col rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <span
                                            className={`rounded px-2 py-1 text-[11px] font-bold ${doc.typeColor}`}
                                        >
                                            {doc.type}
                                        </span>

                                        <span className="max-w-[70%] truncate rounded bg-blue-100 px-2 py-1 text-[11px] font-medium text-blue-800">
                                            {doc.course}
                                        </span>
                                    </div>

                                    <h3 className="mt-4 min-h-[3.5rem] text-base font-bold leading-6 text-slate-900">
                                        {doc.title}
                                    </h3>

                                    <div className="mt-4 flex items-center justify-between gap-2 rounded-xl bg-indigo-50 p-3">
                                        <div className="min-w-0">
                                            <p className="text-xs text-slate-600">
                                                {doc.date}
                                            </p>

                                            <p
                                                className={`mt-1 flex items-center gap-1 text-[11px] font-medium ${doc.sourceColor}`}
                                            >
                                                <SourceIcon size={13} />
                                                {doc.source}
                                            </p>
                                        </div>

                                        <span
                                            title="Tài liệu minh họa, chưa có tệp tải xuống"
                                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm"
                                        >
                                            <Download size={17} />
                                        </span>
                                    </div>
                                </article>
                            );
                        })}
                    </div>

                    <p className="mt-3 text-xs leading-5 text-slate-500">
                        Các tài liệu phía trên là dữ liệu minh họa để hoàn thiện
                        giao diện. Chưa có chức năng tải xuống từ những thẻ này.
                    </p>
                </section>

                {/* 5. Getting started */}
                <section className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                    <div className="mb-5 flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50">
                            <Compass
                                size={21}
                                className="text-blue-800"
                            />
                        </div>

                        <h2 className="text-lg font-bold text-slate-900">
                            Bắt đầu với AGU Academic Hub
                        </h2>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-3">
                        {gettingStarted.map((item) => (
                            <div
                                key={item.number}
                                className="rounded-xl bg-indigo-50/90 p-4 sm:p-5"
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${item.color}`}
                                    >
                                        {item.number}
                                    </span>

                                    <div>
                                        <h3 className="font-bold leading-5 text-slate-900">
                                            {item.title}
                                        </h3>

                                        <p className="mt-3 text-xs leading-5 text-slate-600 sm:text-sm">
                                            {item.description}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </StudentLayout>
    );
}