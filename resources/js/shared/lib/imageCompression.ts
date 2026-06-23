// Compresión de imágenes en el cliente antes de subir por FormData
// (evita superar el límite de cuerpo de las funciones serverless de Vercel).
const DEFAULT_MAX_DIMENSION = 1920;
const DEFAULT_THRESHOLD = 500 * 1024;
const DEFAULT_WEBP_QUALITY = 0.8;

type CompressOptions = {
  enableCompression?: boolean;
  compressionThreshold?: number;
  maxDimension?: number;
  qualityWebp?: number;
};

function loadImageFromFile(file: File): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('No se pudo leer la imagen.'));
    };
    img.src = url;
  });
}

function canvasToBlob(canvas: HTMLCanvasElement, type: string, quality?: number): Promise<Blob | null> {
  return new Promise((resolve) => {
    canvas.toBlob((blob) => resolve(blob), type, quality);
  });
}

export async function compressImageFile(file: File, options: CompressOptions = {}): Promise<File> {
  if (!file || !file.type.startsWith('image/')) {
    return file;
  }

  const enableCompression = options.enableCompression !== false;
  const compressionThreshold = options.compressionThreshold ?? DEFAULT_THRESHOLD;
  const maxDimension = options.maxDimension ?? DEFAULT_MAX_DIMENSION;
  const qualityWebp = options.qualityWebp ?? DEFAULT_WEBP_QUALITY;

  if (!enableCompression || file.size < compressionThreshold) {
    return file;
  }

  try {
    const img = await loadImageFromFile(file);
    let width = img.width;
    let height = img.height;

    const longest = Math.max(width, height);
    if (longest > maxDimension) {
      const scale = maxDimension / longest;
      width = Math.round(width * scale);
      height = Math.round(height * scale);
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      return file;
    }
    ctx.drawImage(img, 0, 0, width, height);

    const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    const convertToWebp = rasterTypes.includes(file.type);
    const mime = convertToWebp ? 'image/webp' : file.type || 'image/jpeg';
    const blob = await canvasToBlob(canvas, mime, convertToWebp ? qualityWebp : undefined);

    if (!blob || blob.size >= file.size) {
      return file;
    }

    const baseName = file.name.replace(/\.[^.]+$/, '');
    const ext = convertToWebp ? '.webp' : mime === 'image/png' ? '.png' : '.jpg';

    return new File([blob], `${baseName}${ext}`, { type: mime, lastModified: Date.now() });
  } catch {
    return file;
  }
}

export async function compressFileList(files: File[], options: CompressOptions = {}): Promise<File[]> {
  return Promise.all(files.map((file) => compressImageFile(file, options)));
}
