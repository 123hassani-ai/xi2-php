/**
 * زیتو (Xi2) - مدیریت آپلود
 * مدیریت آپلود، پردازش و اشتراک‌گذاری تصاویر
 */

class Xi2Upload {
    constructor() {
        this.API_BASE = '/xi2-01/src/api/upload/';
        this.uploadZone = document.getElementById('uploadZone');
        this.fileInput = document.getElementById('fileInput');
        this.selectFilesBtn = document.getElementById('selectFiles');
        this.takePhotoBtn = document.getElementById('takePhoto');
        this.progressContainer = document.getElementById('uploadProgress');
        this.progressBar = document.getElementById('progressFill');
        this.progressText = document.getElementById('progressText');
        this.resultContainer = document.getElementById('uploadResult');
        this.shareLinkInput = document.getElementById('shareLink');
        this.copyLinkBtn = document.getElementById('copyLink');
        this.shareWhatsAppBtn = document.getElementById('shareWhatsApp');

        this.currentFiles = [];
        this.uploadedFiles = [];

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupDragAndDrop();
    }

    setupEventListeners() {
        // انتخاب فایل
        this.selectFilesBtn?.addEventListener('click', () => {
            this.fileInput?.click();
        });

        // عکس گرفتن
        this.takePhotoBtn?.addEventListener('click', () => {
            this.openCamera();
        });

        // تغییر فایل
        this.fileInput?.addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files);
        });

        // کپی لینک
        this.copyLinkBtn?.addEventListener('click', () => {
            this.copyToClipboard();
        });

        // اشتراک واتساپ
        this.shareWhatsAppBtn?.addEventListener('click', () => {
            this.shareOnWhatsApp();
        });

        // کلیک روی منطقه آپلود
        this.uploadZone?.addEventListener('click', () => {
            this.fileInput?.click();
        });
    }

    setupDragAndDrop() {
        if (!this.uploadZone) return;

        // جلوگیری از رفتار پیش‌فرض
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, this.preventDefaults);
            document.body.addEventListener(eventName, this.preventDefaults);
        });

        // هایلایت کردن منطقه آپلود
        ['dragenter', 'dragover'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, () => {
                this.uploadZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, () => {
                this.uploadZone.classList.remove('dragover');
            });
        });

        // مدیریت drop
        this.uploadZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            this.handleFileSelect(files);
        });
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    async handleFileSelect(files) {
        if (!files || files.length === 0) return;

        // فیلتر کردن فقط تصاویر
        const imageFiles = Array.from(files).filter(file => {
            return file.type.startsWith('image/');
        });

        if (imageFiles.length === 0) {
            window.xi2App?.showNotification('لطفاً فقط فایل‌های تصویری انتخاب کنید', 'error');
            return;
        }

        // بررسی حجم فایل‌ها
        const maxSize = 10 * 1024 * 1024; // 10MB
        const oversizedFiles = imageFiles.filter(file => file.size > maxSize);
        
        if (oversizedFiles.length > 0) {
            window.xi2App?.showNotification('حجم فایل نباید بیشتر از 10 مگابایت باشد', 'error');
            return;
        }

        this.currentFiles = imageFiles;
        await this.uploadFiles();
    }

    async openCamera() {
        try {
            // بررسی پشتیبانی دوربین
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                window.xi2App?.showNotification('دوربین در این مرورگر پشتیبانی نمی‌شود', 'error');
                return;
            }

            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment' // دوربین پشت
                } 
            });

            this.showCameraModal(stream);

        } catch (error) {
            console.error('خطا در دسترسی به دوربین:', error);
            window.xi2App?.showNotification('دسترسی به دوربین امکان‌پذیر نیست', 'error');
        }
    }

    showCameraModal(stream) {
        const modal = document.createElement('div');
        modal.className = 'modal camera-modal active';
        modal.innerHTML = `
            <div class="modal-content camera-content">
                <span class="close camera-close">&times;</span>
                <div class="camera-container">
                    <video id="cameraPreview" autoplay playsinline></video>
                    <canvas id="cameraCanvas" style="display: none;"></canvas>
                    <div class="camera-controls">
                        <button id="captureBtn" class="btn btn-primary">📷 عکس بگیرید</button>
                        <button id="switchCameraBtn" class="btn btn-secondary">🔄 چرخش دوربین</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const video = modal.querySelector('#cameraPreview');
        const canvas = modal.querySelector('#cameraCanvas');
        const captureBtn = modal.querySelector('#captureBtn');
        const switchCameraBtn = modal.querySelector('#switchCameraBtn');
        const closeBtn = modal.querySelector('.camera-close');

        video.srcObject = stream;

        // گرفتن عکس
        captureBtn.addEventListener('click', () => {
            this.capturePhoto(video, canvas, stream);
            document.body.removeChild(modal);
        });

        // بستن دوربین
        closeBtn.addEventListener('click', () => {
            this.stopCamera(stream);
            document.body.removeChild(modal);
        });

        // چرخش دوربین
        let currentFacingMode = 'environment';
        switchCameraBtn.addEventListener('click', async () => {
            this.stopCamera(stream);
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            
            try {
                const newStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: currentFacingMode } 
                });
                video.srcObject = newStream;
            } catch (error) {
                console.error('خطا در چرخش دوربین:', error);
            }
        });
    }

    capturePhoto(video, canvas, stream) {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        canvas.toBlob((blob) => {
            const file = new File([blob], `captured-${Date.now()}.jpg`, { type: 'image/jpeg' });
            this.currentFiles = [file];
            this.uploadFiles();
        }, 'image/jpeg', 0.8);

        this.stopCamera(stream);
    }

    stopCamera(stream) {
        stream.getTracks().forEach(track => track.stop());
    }

    async uploadFiles() {
        if (this.currentFiles.length === 0) return;

        // نمایش پیشرفت
        this.showProgress();
        
        try {
            const uploadPromises = this.currentFiles.map(file => this.uploadSingleFile(file));
            const results = await Promise.all(uploadPromises);
            
            this.uploadedFiles = results.filter(result => result.success);
            
            if (this.uploadedFiles.length > 0) {
                this.showResult();
                window.xi2App?.showNotification(
                    `${this.uploadedFiles.length} تصویر با موفقیت آپلود شد!`, 
                    'success'
                );
            } else {
                throw new Error('هیچ فایلی آپلود نشد');
            }

        } catch (error) {
            console.error('خطا در آپلود:', error);
            window.xi2App?.showNotification('خطا در آپلود فایل‌ها', 'error');
            this.hideProgress();
        }
    }

    async uploadSingleFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        try {
            const xhr = new XMLHttpRequest();
            
            // اضافه کردن توکن احراز هویت اگر موجود باشد
            const token = window.xi2Auth?.getToken();
            if (token) {
                xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            }
            
            return new Promise((resolve, reject) => {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        this.updateProgress(percentComplete);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200 || xhr.status === 201) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve({
                                    success: true,
                                    data: response.data.upload
                                });
                            } else {
                                reject(new Error(response.message || 'خطا در آپلود'));
                            }
                        } catch (error) {
                            reject(new Error('خطا در پردازش پاسخ سرور'));
                        }
                    } else {
                        reject(new Error(`خطای سرور: ${xhr.status}`));
                    }
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('خطا در برقراری ارتباط'));
                });

                xhr.open('POST', this.API_BASE + 'upload.php');
                xhr.send(formData);
            });

        } catch (error) {
            console.error('خطا در آپلود فایل:', error);
            return { success: false, error: error.message };
        }
    }
                    reject(new Error('خطا در ارتباط با سرور'));
                });

                xhr.open('POST', '/src/api/upload/upload.php');
                xhr.send(formData);
            });

        } catch (error) {
            throw error;
        }
    }

    showProgress() {
        this.uploadZone.style.display = 'none';
        this.progressContainer.style.display = 'block';
        this.resultContainer.style.display = 'none';
        
        this.updateProgress(0);
        this.progressText.textContent = 'شروع آپلود...';
    }

    updateProgress(percent) {
        this.progressBar.style.width = `${percent}%`;
        
        if (percent < 30) {
            this.progressText.textContent = 'در حال آماده‌سازی...';
        } else if (percent < 70) {
            this.progressText.textContent = 'در حال آپلود...';
        } else if (percent < 90) {
            this.progressText.textContent = 'در حال پردازش...';
        } else {
            this.progressText.textContent = 'تقریباً تمام شد...';
        }
    }

    hideProgress() {
        this.progressContainer.style.display = 'none';
        this.uploadZone.style.display = 'block';
    }

    showResult() {
        this.progressContainer.style.display = 'none';
        this.resultContainer.style.display = 'block';
        
        // اگر یک فایل آپلود شده، لینک مستقیم نمایش بده
        if (this.uploadedFiles.length === 1) {
            const uploadedFile = this.uploadedFiles[0];
            this.shareLinkInput.value = uploadedFile.share_url;
        } else {
            // اگر چند فایل، لینک گالری
            this.shareLinkInput.value = `${window.location.origin}/gallery/${Date.now()}`;
        }
    }

    copyToClipboard() {
        this.shareLinkInput.select();
        this.shareLinkInput.setSelectionRange(0, 99999); // موبایل
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(this.shareLinkInput.value).then(() => {
                window.xi2App?.showNotification('لینک کپی شد!', 'success');
            });
        } else {
            document.execCommand('copy');
            window.xi2App?.showNotification('لینک کپی شد!', 'success');
        }
    }

    shareOnWhatsApp() {
        const url = encodeURIComponent(this.shareLinkInput.value);
        const text = encodeURIComponent('تصویری برای شما از زیتو:');
        const whatsappUrl = `https://wa.me/?text=${text}%20${url}`;
        
        window.open(whatsappUrl, '_blank');
    }

    // بازنشانی فرم
    reset() {
        this.currentFiles = [];
        this.uploadedFiles = [];
        this.progressContainer.style.display = 'none';
        this.resultContainer.style.display = 'none';
        this.uploadZone.style.display = 'block';
        this.fileInput.value = '';
    }
}

// راه‌اندازی آپلود
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Upload = new Xi2Upload();
});

// CSS اضافی برای دوربین
const cameraStyles = document.createElement('style');
cameraStyles.textContent = `
.camera-modal .modal-content {
    max-width: 90vw;
    max-height: 90vh;
    padding: var(--spacing-lg);
}

.camera-container {
    text-align: center;
}

#cameraPreview {
    width: 100%;
    max-width: 400px;
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
}

.camera-controls {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .camera-controls {
        flex-direction: column;
    }
}
`;
document.head.appendChild(cameraStyles);
