import { useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import ApplicationLogo from "@/Components/ApplicationLogo";
import FacultyCourseTree from "./FacultyCourseTree";
import {
    LayoutDashboard,
    BookOpen,
    GraduationCap,
    CloudUpload,
    Coins,
    ShieldCheck,
    X,
} from "lucide-react";
export default function StudentSidebar({
    faculties = [],
    isOpen = false,
    onClose = () => { },
}) {
    // Lấy URL hiện tại để xác định mục menu đang được chọn
    const page = usePage();
    const user = page.props?.auth?.user;
    const isStaff = Boolean(
        user?.la_quan_tri ||
        user?.la_kiem_duyet ||
        user?.vai_tro === "QUAN_TRI_VIEN" ||
        user?.vai_tro === "KIEM_DUYET_VIEN"
    );

    // Đóng drawer khi nhấn phím Escape và khóa cuộn body trên mobile
    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e) => {
            if (e.key === "Escape") {
                onClose();
            }
        };

        document.addEventListener("keydown", handleKeyDown);

        if (typeof window !== "undefined" && window.innerWidth < 1024) {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = "hidden";
            return () => {
                document.removeEventListener("keydown", handleKeyDown);
                document.body.style.overflow = originalOverflow;
            };
        }

        return () => {
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [isOpen, onClose]);

    // danh sách menu
    const menuItems = [
        ...(isStaff ? [{
            label: "Trang Quản trị",
            href: "/quan-tri",
            icon: ShieldCheck,
            badge: "Admin",
        }] : []),
        {
            label: "Tổng quan",
            href: "/trang-chu",
            icon: LayoutDashboard,
        },
        {
            label: "Kho môn học",
            href: "/mon-hoc",
            icon: BookOpen,
        },
        isStaff ? {
            label: "Duyệt Bounty",
            href: "/quan-tri/yeu-cau-tai-lieu/cho-giai-ngan",
            icon: Coins,
            badge: "Admin",
        } : {
            label: "Săn thưởng tài liệu",
            href: "/yeu-cau-tai-lieu",
            icon: Coins,
            badge: "Bounty",
        },
        {
            label: "Danh sách khoa",
            href: "/khoa",
            icon: GraduationCap,
        },
        {
            label: "Đăng tài liệu",
            href: "/tai-lieu/dang-tai",
            icon: CloudUpload,
        },
    ];
    return (
        <>
            {/* Lớp nền mờ khi mở sidebar trên mobile / tablet */}
            {isOpen && (
                <div
                    role="button"
                    tabIndex={0}
                    aria-label="Đóng menu"
                    onClick={onClose}
                    onKeyDown={(e) => {
                        if (e.key === "Enter" || e.key === " ") onClose();
                    }}
                    className="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300 lg:hidden cursor-pointer"
                />
            )}
            <aside
                aria-label="Sidebar điều hướng"
                className={`
                    fixed inset-y-0 left-0 z-50
                    flex w-[280px] sm:w-[300px] flex-col border-r border-slate-200 bg-white
                    transition-transform duration-300 ease-in-out shadow-2xl
                    lg:static lg:w-[260px] lg:translate-x-0 lg:shadow-none lg:shrink-0
                    ${isOpen ? "translate-x-0" : "-translate-x-full"}
                `}
            >
                {/* Header drawer riêng cho mobile */}
                <div className="flex h-16 items-center justify-between border-b border-slate-200 px-4 bg-[#005BAA] text-white lg:hidden shrink-0">
                    <div className="flex items-center gap-2.5">
                        <ApplicationLogo className="w-8 h-8" alt="Logo AGU" />
                        <div>
                            <h2 className="font-bold text-[#FFC72C] leading-tight text-sm">Academic Hub</h2>
                            <p className="font-medium text-[10px] text-white">ĐHQG-HCM - AGU</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1.5 text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition-colors cursor-pointer"
                        aria-label="Đóng menu"
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* menu dieu huong*/}
                <div className="space-y-1 border-b border-slate-200 px-4 py-4">
                    {menuItems.map((item) => {
                        const Icon = item.icon;
                        const isActive =
                            page.url === item.href || (item.href !== "/trang-chu" && page.url.startsWith(item.href));
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={`
                                flex h-10 items-center gap-3 rounded-lg px-3.5 text-sm font-medium transition-colors
                                ${isActive
                                        ? "bg-blue-50 text-[#005baa] font-semibold border-l-2 border-[#005baa]"
                                        : "text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                                    }
                                `}
                            >
                                <Icon size={18} strokeWidth={isActive ? 2 : 1.75} className="shrink-0" />
                                <span className="flex-1 truncate">{item.label}</span>
                                {item.badge && (
                                    <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                        {item.badge}
                                    </span>
                                )}
                            </Link>
                        );
                    })}
                </div>
                {/* danh sach khoa va mon hoc */}
                <div className="flex-1 overflow-y-auto p-4">
                    <FacultyCourseTree
                        faculties={faculties}
                        onItemClick={onClose}
                    />
                </div>
                {/* footer sidebar */}
                <div className="border-t p-4 text-xs text-gray-500">
                    <p> AGU Hub v1.0</p>
                    <p> Online</p>
                </div>
            </aside>
        </>
    )
}


