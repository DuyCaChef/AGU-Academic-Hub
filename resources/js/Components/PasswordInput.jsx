import { forwardRef, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import TextInput from '@/Components/TextInput';

/**
 * Ô nhập mật khẩu có nút con mắt bật/tắt hiển thị mật khẩu.
 */
export default forwardRef(function PasswordInput(
    { className = '', ...props },
    ref,
) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <div className="relative mt-1.5">
            <TextInput
                {...props}
                ref={ref}
                type={showPassword ? 'text' : 'password'}
                className={`block w-full rounded-xl border-slate-300 px-3.5 py-2.5 pr-11 text-sm shadow-2xs focus:border-[#005baa] focus:ring-[#005baa] ${className}`}
            />
            <button
                type="button"
                onClick={() => setShowPassword((prev) => !prev)}
                aria-label={showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
                title={showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
                className="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus:text-[#005baa]"
            >
                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
        </div>
    );
});
