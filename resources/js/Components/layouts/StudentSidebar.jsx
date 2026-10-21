import { Link, usePage } from "@inertiajs/react";
import KhoaVaMonHocTree from "./KhoaVaMonHocTree";
import {
    LayoutDashboard,
    BookOpen,
    GraduationCap,
    CloudUpload,
} from "lucide-react";
export default function StudentSidebar({
    faculties = [],
    isOpen = false,
    onClose = () => { },
}) {
    // Lấy URL hiện tại để xác định mục menu đang được chọn
    const page = usePage();
    // danh sách menu
    const menuItems = [
        {
            label: "Tổng quan",
            href: "/dashboard",
            icon: LayoutDashboard,
        },
        {
            label: "Kho môn học",
            href: "/kho-tai-lieu",
            icon: BookOpen,
        },
        {
            label: "Danh sách khoa",
            href: "/danh-sach-khoa",
            icon: GraduationCap,
        },
        {
            label: "Đăng tài liệu",
            href: "/tai-lieu/tao",
            icon: CloudUpload,
        },
    ];
    return (
        <>
            {/* Lop nen khi mo sidebar tren mobile */}
            {isOpen && (
                <button
                    type="button"
                    aria-label="Đóng menu"
                    onClick={onClose}
                    className="fixed inset-0 z-40 bg-white md:hidden"
                />
            )}
            <aside
                className={`
                fixed inset-y-0 left-0 z-50
                flex w-[260px] flex-col border-r bg-white
                transition-transform duration-200
                md:static md:translate-x-0
                ${isOpen ? "translate-x-0" : "-translate-x-full"} 
             `}
            >
                {/* menu dieu huong*/}
                <div className="space-y-1 border-b border-slate-200 px-4 py-4">
                    {menuItems.map((item) => {
                        const Icon = item.icon;
                        const isActive =
                            page.url === item.href || (item.href !== "/dashboard" && page.url.startsWith(item.href));
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={`
                                flex h-10 items-center gap-3 rounded-lg px-4 text-sm font-medium transition-colors
                                ${isActive
                                        ? "bg-indigo-100 text-blue-800"
                                        : "text-slate-600 hover:bg-slate-100"
                                    }
                                `}
                            >
                                <Icon size={18} strokeWidth={2} />
                                <span>{item.label}</span>
                            </Link>
                        )
                    })}
                </div>
                {/* danh sach khoa va mon hoc */}
                <div className="flex-1 overflow-y-auto p-4">
                    <KhoaVaMonHocTree
                        faculities={faculties}
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


