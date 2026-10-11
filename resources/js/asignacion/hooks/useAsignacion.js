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
            const envio = data.envio;
            const transportistas = data.transportistas || [];

            const assignedTransIds = [
                ...new Set(
                    (envio.items || [])
                        .map((i) => i.transportista_id)
                        .filter(Boolean)
                ),
            ];

            const columnasIniciales = transportistas.filter((t) =>
                assignedTransIds.includes(t.id)
            );
            setEnvioActivo(envio);
            setTransportistasDisponibles(transportistas);
            setColumnasActivas(columnasIniciales);
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
            const envio = data.envio;
            setEnvioActivo(envio);

            const assignedTransIds = (envio.items || [])
                .map((i) => i.transportista_id)
                .filter(Boolean);

            setColumnasActivas((prev) => {
                const prevIds = prev.map((t) => t.id);
                const nuevos = transportistasDisponibles.filter(
                    (t) => assignedTransIds.includes(t.id) && !prevIds.includes(t.id)
                );
                return [...prev, ...nuevos];
            });

        } catch (err) {
            showToast(err.message, 'error');
        }
    }, [envioActivo, transportistasDisponibles, showToast]);

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


    const splitItem = useCallback(async (itemId, cantidades) => {
        try {
            const data = await api.post('/api/asignacion-carga/split', {
                item_id: itemId,
                cantidades: cantidades,
            });
            showToast(data.message || 'Carga dividida con éxito');
            await reloadEnvio();
            return true;
        } catch (err) {
            showToast(err.message, 'error');
            throw err;
        }
    }, [showToast, reloadEnvio]);

    const mergeItems = useCallback(async (itemOrigenId, itemDestinoId) => {
        try {
            const data = await api.post('/api/asignacion-carga/merge', {
                item_id_origen: itemOrigenId,
                item_id_destino: itemDestinoId,
            });
            showToast(data.message || 'Cargas consolidadas exitosamente');
            await reloadEnvio();
            return true;
        } catch (err) {
            showToast(err.message, 'error');
            throw err;
        }
    }, [showToast, reloadEnvio]);

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
        splitItem,
        mergeItems,
    };
}
