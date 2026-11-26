
import { useState } from "react";
import { usePage } from "@inertiajs/react";

import StudentHeader from "../Components/layouts/StudentHeader";
import StudentSidebar from "../Components/layouts/StudentSidebar";
import StudentFooter from "../Components/layouts/StudentFooter";

export default function StudentLayout({
    children,
    title = "Cổng Học Tập Sinh Viên",
}) {
    const { faculties = [] } = usePage().props;
    const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);

    const handleMenuClick = () => {
        setIsMobileSidebarOpen((current) => !current);
    };

    const handleCloseSidebar = () => {
        setIsMobileSidebarOpen(false);
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 font-sans flex flex-col">
            {/* Header dùng chung */}
            <StudentHeader
                onMenuClick={handleMenuClick}
                isMobileSidebarOpen={isMobileSidebarOpen}
            />

            {/* Sidebar và nội dung trang */}
            <div className="pt-16 flex flex-1 min-h-0 overflow-hidden">
                <StudentSidebar
                    faculties={faculties}
                    isOpen={isMobileSidebarOpen}
                    onClose={handleCloseSidebar}
                />

                {/* Nội dung của từng trang */}
                <main className="flex-1 min-w-0 overflow-y-auto bg-slate-50 flex flex-col justify-between">
                    <div className="flex-1 p-4 md:p-6 lg:p-8">
                        <div className="max-w-7xl mx-auto space-y-6">
                            {children}
                        </div>
                    </div>
                    <StudentFooter />
                </main>
            </div>
        </div>
    );
}
