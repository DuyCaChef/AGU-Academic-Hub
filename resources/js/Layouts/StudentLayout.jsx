
import { useState } from "react";
import { usePage } from "@inertiajs/react";

import StudentHeader from "../Components/layouts/StudentHeader";
import StudentSidebar from "../Components/layouts/StudentSidebar";

export default function StudentLayout({
    children,
    title = "Cổng Học Tập Sinh Viên",
}) {
    const { faculties = [] } = usePage().props;

    const [isMobileSidebarOpen, setIsMobileSidebarOpen] =
        useState(false);

    const handleMenuClick = () => {
        setIsMobileSidebarOpen((current) => !current);
    };

    const handleCloseSidebar = () => {
        setIsMobileSidebarOpen(false);
    };

    return (
        <div className="min-h-screen bg-surface-page text-text-primary font-sans flex flex-col">
            {/* Header dùng chung */}
            <StudentHeader
                onMenuClick={handleMenuClick}
            />

            {/* Sidebar và nội dung trang */}
            <div className="flex flex-1 min-h-0 overflow-hidden">
                <StudentSidebar
                    faculties={faculties}
                    isOpen={isMobileSidebarOpen}
                    onClose={handleCloseSidebar}
                />

                {/* Nội dung của từng trang */}
                <main className="flex-1 min-w-0 overflow-y-auto p-4 md:p-6 lg:p-8 bg-surface-page">
                    <div className="max-w-6xl mx-auto space-y-6">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
