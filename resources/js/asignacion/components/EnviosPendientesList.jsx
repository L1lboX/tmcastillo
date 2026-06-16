export function EnviosPendientesList({ envios, onSelect, loading }) {
    if (loading && envios.length === 0) {
        return (
            <div className="kanban-loading">
                <div className="kanban-spinner" />
                <p>Cargando envíos pendientes...</p>
            </div>
        );
    }

    if (envios.length === 0) {
        return (
            <div className="envios-lista-empty">
                <p>No hay envíos con items pendientes de asignación</p>
            </div>
        );
    }

    return (
        <div className="envios-lista">
            <div className="envios-lista-header">
                <span>Envío</span>
                <span>Cliente</span>
                <span>Fecha</span>
                <span>Progreso</span>
                <span></span>
            </div>
            {envios.map((envio) => (
                <div
                    key={envio.id}
                    className="envios-lista-row"
                    onClick={() => onSelect(envio.id)}
                >
                    <div className="envios-lista-cell">
                        <span className="envios-lista-code">#{envio.codigo || envio.id}</span>
                    </div>
                    <div className="envios-lista-cell">
                        {envio.cliente || 'Sin cliente'}
                    </div>
                    <div className="envios-lista-cell envios-lista-fecha">
                        {envio.fecha || '-'}
                    </div>
                    <div className="envios-lista-cell">
                        <div className="envios-lista-progress">
                            <div className="envios-lista-progress-bar">
                                <div
                                    className="envios-lista-progress-fill"
                                    style={{
                                        width: `${envio.total_items > 0 ? (envio.items_asignados / envio.total_items) * 100 : 0}%`,
                                    }}
                                />
                            </div>
                            <span className="envios-lista-progress-text">
                                {envio.items_sin_asignar}/{envio.total_items} sin asignar
                            </span>
                        </div>
                    </div>
                    <div className="envios-lista-cell envios-lista-arrow">
                        →
                    </div>
                </div>
            ))}
        </div>
    );
}
