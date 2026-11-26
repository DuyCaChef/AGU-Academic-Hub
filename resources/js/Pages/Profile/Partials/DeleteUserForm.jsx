import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PasswordInput from '@/Components/PasswordInput';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Xóa tài khoản
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Khi tài khoản bị xóa, toàn bộ tài nguyên và dữ liệu liên quan
                    sẽ bị xóa vĩnh viễn. Trước khi thực hiện, vui lòng tải xuống
                    các dữ liệu hoặc thông tin bạn muốn giữ lại.
                </p>
            </header>

            <DangerButton onClick={confirmUserDeletion}>
                Xóa tài khoản
            </DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Bạn có chắc chắn muốn xóa tài khoản?
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        Khi tài khoản bị xóa, toàn bộ tài nguyên và dữ liệu sẽ bị
                        xóa vĩnh viễn. Vui lòng nhập mật khẩu để xác nhận bạn muốn
                        xóa vĩnh viễn tài khoản của mình.
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value="Mật khẩu"
                            className="sr-only"
                        />

                        <PasswordInput
                            id="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            isFocused
                            placeholder="Nhập mật khẩu"
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>
                            Hủy
                        </SecondaryButton>

                        <DangerButton className="ms-3" disabled={processing}>
                            Xóa tài khoản
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
