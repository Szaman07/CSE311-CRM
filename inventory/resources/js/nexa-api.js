export async function nexaFetch(path, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const response = await fetch(path, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(token ? {'X-CSRF-TOKEN': token} : {}),
            ...(options.headers || {}),
        },
    });
    if (response.status === 204) return null;
    const body = await response.json();
    if (!response.ok) {
        const error = new Error(body.error?.message || 'Request failed.');
        error.code = body.error?.code;
        error.fields = body.error?.fields;
        error.requestId = body.error?.request_id;
        throw error;
    }
    if (body.data?.csrf_token && token) {
        document.querySelector('meta[name="csrf-token"]').content = body.data.csrf_token;
    }
    return body;
}

export const api = nexaFetch;
export const newRequestKey = () => crypto.randomUUID();
