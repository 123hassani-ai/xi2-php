/**
 * زیتو (Xi2) - مدیریت آپلود
 * مدیریت آپلود، پردازش و اشتراک‌گذاری تصاویر
 */

class Xi2Upload {
    constructor() {
        this.API_BASE = '/xi2.ir/src/api/upload/';
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
    }

    setupDragAndDrop() {
        if (!this.uploadZone) return;

        // جلوگیری از باز شدن فایل در مرورگر
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, this.preventDefaults, false);
            document.body.addEventListener(eventName, this.preventDefaults, false);
        });

        // هایلایت کردن منطقه drag
        ['dragenter', 'dragover'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, () => {
                this.uploadZone.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.uploadZone.addEventListener(eventName, () => {
                this.uploadZone.classList.remove('drag-over');
            }, false);
        });

        // مدیریت drop
        this.uploadZone.addEventListener('drop', (e) => {
            this.handleFileSelect(e.dataTransfer.files);
        }, false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    handleFileSelect(files) {
        const fileArray = Array.from(files);
        
        // فیلتر فایل‌های تصویری
        const imageFiles = fileArray.filter(file => {
            return file.type.startsWith('image/');
        });

        if (imageFiles.length === 0) {
            window.xi2App?.showNotification('لطفاً فقط فایل‌های تصویری انتخاب کنید', 'warning');
            return;
        }

        // بررسی حجم فایل‌ها
        const maxSize = 10 * 1024 * 1024; // 10MB
        const oversizedFiles = imageFiles.filter(file => file.size > maxSize);
        
        if (oversizedFiles.length > 0) {
            window.xi2App?.showNotification(
                `${oversizedFiles.length} فایل بزرگتر از 10 مگابایت هستند`, 
                'warning'
            );
        }

        this.currentFiles = imageFiles.filter(file => file.size <= maxSize);
        
        if (this.currentFiles.length > 0) {
            this.previewFiles();
            this.uploadFiles();
        }
    }

    async openCamera() {
        try {
            // بررسی پشتیبانی از دوربین
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                window.xi2App?.showNotification('دوربین در این مرورگر پشتیبانی نمی‌شود', 'error');
                return;
            }

            // درخواست دسترسی به دوربین
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment' // دوربین عقب در موبایل
                } 
            });

            // ایجاد video element برای پیش‌نمایش
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();

            // ایجاد مودال دوربین
            this.showCameraModal(video, stream);

        } catch (error) {
            console.error('خطا در دسترسی به دوربین:', error);
            window.xi2App?.showNotification('عدم دسترسی به دوربین', 'error');
        }
    }

    showCameraModal(video, stream) {
        const modalHTML = `
            <div class="camera-modal" id="cameraModal">
                <div class="camera-container">
                    <div class="camera-preview">
                        <video id="cameraVideo" autoplay playsinline></video>
                        <canvas id="cameraCanvas" style="display: none;"></canvas>
                    </div>
                    <div class="camera-controls">
                        <button id="captureBtn" class="capture-btn">📸 عکس بگیر</button>
                        <button id="closeCameraBtn" class="close-camera-btn">❌ بستن</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById('cameraModal');
        const videoElement = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const captureBtn = document.getElementById('captureBtn');
        const closeBtn = document.getElementById('closeCameraBtn');

        videoElement.srcObject = stream;

        captureBtn.addEventListener('click', () => {
            this.capturePhoto(videoElement, canvas, stream);
            modal.remove();
        });

        closeBtn.addEventListener('click', () => {
            stream.getTracks().forEach(track => track.stop());
            modal.remove();
        });
    }

    capturePhoto(video, canvas, stream) {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        context.drawImage(video, 0, 0);
        
        canvas.toBlob((blob) => {
            const file = new File([blob], `camera-${Date.now()}.jpg`, { type: 'image/jpeg' });
            this.currentFiles = [file];
            this.previewFiles();
            this.uploadFiles();
        }, 'image/jpeg', 0.8);

        // بستن stream
        stream.getTracks().forEach(track => track.stop());
    }

    previewFiles() {
        if (!this.uploadZone) return;

        const previewHTML = this.currentFiles.map((file, index) => `
            <div class="file-preview" data-index="${index}">
                <img src="${URL.createObjectURL(file)}" alt="${file.name}">
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${this.formatFileSize(file.size)}</div>
                </div>
                <button class="remove-file" onclick="window.xi2Upload.removeFile(${index})">❌</button>
            </div>
        `).join('');

        this.uploadZone.innerHTML = `
            <div class="preview-container">
                ${previewHTML}
            </div>
            <button class="upload-more-btn" onclick="document.getElementById('fileInput').click()">
                ➕ افزودن فایل بیشتر
            </button>
        `;
    }

    removeFile(index) {
        this.currentFiles.splice(index, 1);
        
        if (this.currentFiles.length === 0) {
            this.reset();
        } else {
            this.previewFiles();
        }
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
                
                // اطلاع‌رسانی به بقیه اجزا برای به‌روزرسانی
                document.dispatchEvent(new CustomEvent('filesUploaded', {
                    detail: { files: this.uploadedFiles }
                }));
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
                
                // اضافه کردن توکن احراز هویت اگر موجود باشد
                const token = window.xi2Auth?.getToken();
                if (token) {
                    xhr.setRequestHeader('Authorization', `Bearer ${token}`);
                }
                
                xhr.send(formData);
            });

        } catch (error) {
            console.error('خطا در آپلود فایل:', error);
            return { success: false, error: error.message };
        }
    }

    showProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.display = 'block';
        }
        this.updateProgress(0);
    }

    updateProgress(percent) {
        if (this.progressBar) {
            this.progressBar.style.width = `${percent}%`;
        }
        
        if (this.progressText) {
            if (percent < 100) {
                this.progressText.textContent = `در حال آپلود... ${Math.round(percent)}%`;
            } else {
                this.progressText.textContent = 'در حال پردازش...';
            }
        }
    }

    hideProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.display = 'none';
        }
    }

    showResult() {
        if (!this.resultContainer || this.uploadedFiles.length === 0) return;

        const firstFile = this.uploadedFiles[0].data;
        
        if (this.shareLinkInput) {
            this.shareLinkInput.value = firstFile.shareUrl;
        }

        this.resultContainer.style.display = 'block';
        this.hideProgress();

        // فوکوس روی لینک برای کپی آسان
        setTimeout(() => {
            this.shareLinkInput?.select();
        }, 100);
    }

    copyToClipboard() {
        if (!this.shareLinkInput) return;

        this.shareLinkInput.select();
        document.execCommand('copy');
        
        window.xi2App?.showNotification('لینک کپی شد!', 'success');
        
        // تغییر متن دکمه موقتاً
        const originalText = this.copyLinkBtn.textContent;
        this.copyLinkBtn.textContent = '✅ کپی شد';
        
        setTimeout(() => {
            this.copyLinkBtn.textContent = originalText;
        }, 2000);
    }

    shareOnWhatsApp() {
        if (!this.shareLinkInput || !this.shareLinkInput.value) return;

        const shareUrl = this.shareLinkInput.value;
        const message = `تصویر را از زیتو مشاهده کنید: ${shareUrl}`;
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        
        window.open(whatsappUrl, '_blank');
    }

    formatFileSize(bytes) {
        const sizes = ['بایت', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 بایت';
        
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        const size = (bytes / Math.pow(1024, i)).toFixed(1);
        
        return `${size} ${sizes[i]}`;
    }

    reset() {
        this.currentFiles = [];
        this.uploadedFiles = [];
        
        if (this.uploadZone) {
            this.uploadZone.innerHTML = `
                <div class="upload-icon">📸</div>
                <div class="upload-text">
                    <h3>تصاویر خود را اینجا رها کنید</h3>
                    <p>یا کلیک کنید تا فایل انتخاب کنید</p>
                </div>
                <div class="upload-formats">
                    فرمت‌های مجاز: JPG, PNG, GIF, WebP
                </div>
            `;
        }

        if (this.resultContainer) {
            this.resultContainer.style.display = 'none';
        }

        this.hideProgress();
    }

    // متد برای دریافت لیست آپلودها
    async getUserUploads(page = 1, limit = 20) {
        try {
            const token = window.xi2Auth?.getToken();
            if (!token) {
                throw new Error('احراز هویت لازم است');
            }

            const response = await fetch(`${this.API_BASE}list.php?page=${page}&limit=${limit}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            console.error('خطا در دریافت لیست آپلودها:', error);
            throw error;
        }
    }

    // متد برای حذف فایل
    async deleteUpload(uploadId) {
        try {
            const token = window.xi2Auth?.getToken();
            if (!token) {
                throw new Error('احراز هویت لازم است');
            }

            const response = await fetch(`${this.API_BASE}delete.php?id=${uploadId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            console.error('خطا در حذف فایل:', error);
            throw error;
        }
    }
}

// راه‌اندازی سراسری
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Upload = new Xi2Upload();
});

// استایل‌های CSS برای آپلود
const uploadStyles = document.createElement('style');
uploadStyles.textContent = `
.upload-zone {
    border: 2px dashed #e1e5e9;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #f8f9fa;
}

.upload-zone:hover,
.upload-zone.drag-over {
    border-color: #6366f1;
    background: #f0f0ff;
    transform: translateY(-2px);
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.upload-text h3 {
    margin: 0 0 8px 0;
    color: #1f2937;
}

.upload-text p {
    margin: 0 0 16px 0;
    color: #6b7280;
}

.upload-formats {
    font-size: 12px;
    color: #9ca3af;
}

.preview-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.file-preview {
    position: relative;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

.file-preview img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.file-info {
    padding: 8px;
    font-size: 12px;
}

.file-name {
    font-weight: 500;
    margin-bottom: 4px;
    word-break: break-word;
}

.file-size {
    color: #6b7280;
}

.remove-file {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,0.7);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 12px;
}

.upload-more-btn {
    width: 100%;
    padding: 12px;
    border: 1px dashed #6366f1;
    background: none;
    border-radius: 8px;
    color: #6366f1;
    cursor: pointer;
    transition: all 0.2s;
}

.upload-more-btn:hover {
    background: #f0f0ff;
}

.camera-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.camera-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    max-width: 400px;
    width: 90%;
}

.camera-preview {
    position: relative;
}

#cameraVideo {
    width: 100%;
    height: auto;
    display: block;
}

.camera-controls {
    display: flex;
    padding: 16px;
    gap: 12px;
}

.capture-btn,
.close-camera-btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.capture-btn {
    background: #6366f1;
    color: white;
}

.capture-btn:hover {
    background: #5855eb;
}

.close-camera-btn {
    background: #ef4444;
    color: white;
}

.close-camera-btn:hover {
    background: #dc2626;
}

@media (max-width: 768px) {
    .preview-container {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .file-preview img {
        height: 100px;
    }
}
`;
document.head.appendChild(uploadStyles);
