import { useState, useCallback } from 'react';

const api = {
    async request(method, url, body = null) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
        if (body) options.body = JSON.stringify(body);
        const res = await fetch(url, options);
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message || `Error ${res.status}`);
        }
        return res.json();
    },
    get: (url) => api.request('GET', url),
    post: (url, body) => api.request('POST', url, body),
};

export function useAsignacion() {
    const [enviosPendientes, setEnviosPendientes] = useState([]);
    const [envioActivo, setEnvioActivo] = useState(null);
    const [transportistasDisponibles, setTransportistasDisponibles] = useState([]);
    const [columnasActivas, setColumnasActivas] = useState([]);
    const [view, setView] = useState('lista');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [toast, setToast] = useState(null);

    const showToast = useCallback((message, type = 'success') => {
        setToast({ message, type });
        setTimeout(() => setToast(null), 3000);
    }, []);

    const loadPendientes = useCallback(async () => {
        try {
            setLoading(true);
            const data = await api.get('/api/asignacion-carga');
            setEnviosPendientes(data.envios);
            setError(null);
        } catch (err) {
            setError(err.message);
            showToast(err.message, 'error');
        } finally {
            setLoading(false);
        }
    }, [showToast]);

    const selectEnvio = useCallback(async (envioId) => {
        try {
            setLoading(true);
            const data = await api.get(`/api/asignacion-carga/${envioId}`);
            setEnvioActivo(data.envio);
            setTransportistasDisponibles(data.transportistas);
            setColumnasActivas([]);
            setView('kanban');
            setError(null);
        } catch (err) {
            setError(err.message);
            showToast(err.message, 'error');
        } finally {
            setLoading(false);
        }
    }, [showToast]);

    const volverALista = useCallback(async () => {
        setView('lista');
        setEnvioActivo(null);
        setColumnasActivas([]);
        setTransportistasDisponibles([]);
        await loadPendientes();
    }, [loadPendientes]);

    const agregarTransportista = useCallback((transportista) => {
        setColumnasActivas((prev) => {
            if (prev.some((t) => t.id === transportista.id)) return prev;
            return [...prev, transportista];
        });
    }, []);

    const quitarTransportista = useCallback(async (transportistaId) => {
        if (!envioActivo) {
            setColumnasActivas((prev) => prev.filter((t) => t.id !== transportistaId));
            return;
        }
        const itemsDelTransportista = envioActivo.items
            .filter((i) => i.transportista_id === transportistaId)
            .map((i) => i.id);
        if (itemsDelTransportista.length > 0) {
            try {
                await api.post('/api/asignacion-carga/bulk-desasignar', {
                    item_ids: itemsDelTransportista,
                });
                showToast(`${itemsDelTransportista.length} items desasignados`);
            } catch (err) {
                showToast(err.message, 'error');
            }
        }
        setColumnasActivas((prev) => prev.filter((t) => t.id !== transportistaId));
        const data = await api.get(`/api/asignacion-carga/${envioActivo.id}`);
        setEnvioActivo(data.envio);
    }, [envioActivo, showToast]);

    const reloadEnvio = useCallback(async () => {
        if (!envioActivo) return;
        try {
            const data = await api.get(`/api/asignacion-carga/${envioActivo.id}`);
            setEnvioActivo(data.envio);
        } catch (err) {
            showToast(err.message, 'error');
        }
    }, [envioActivo, showToast]);

    const asignarItem = useCallback(async (itemId, transportistaId) => {
        try {
            await api.post('/api/asignacion-carga/asignar', {
                item_id: itemId,
                transportista_id: transportistaId,
            });
            showToast('Item asignado correctamente');
            await reloadEnvio();
            return true;
        } catch (err) {
            showToast(err.message, 'error');
            return false;
        }
    }, [showToast, reloadEnvio]);

    const desasignarItem = useCallback(async (itemId) => {
        try {
            await api.post('/api/asignacion-carga/desasignar', {
                item_id: itemId,
            });
            showToast('Item desasignado');
            await reloadEnvio();
            return true;
        } catch (err) {
            showToast(err.message, 'error');
            return false;
        }
    }, [showToast, reloadEnvio]);

    const asignarTodosATransportista = useCallback(async (transportistaId) => {
        if (!envioActivo) return;
        try {
            const sinAsignar = envioActivo.items
                .filter((i) => !i.transportista_id)
                .map((i) => i.id);
            if (sinAsignar.length === 0) {
                showToast('No hay items sin asignar', 'error');
                return;
            }
            await api.post('/api/asignacion-carga/bulk', {
                item_ids: sinAsignar,
                transportista_id: transportistaId,
            });
            showToast(`${sinAsignar.length} items asignados`);
            await reloadEnvio();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }, [envioActivo, showToast, reloadEnvio]);

    return {
        enviosPendientes,
        envioActivo,
        transportistasDisponibles,
        columnasActivas,
        view,
        loading,
        error,
        toast,
        loadPendientes,
        selectEnvio,
        volverALista,
        agregarTransportista,
        quitarTransportista,
        asignarItem,
        desasignarItem,
        asignarTodosATransportista,
    };
}
