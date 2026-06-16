import { useDraggable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';

const ENVIO_COLORS = [
    '#c5a043', '#60a5fa', '#4ade80', '#f59e0b', '#ef6464',
    '#a78bfa', '#f472b6', '#34d399', '#fbbf24', '#fb923c',
];

function getEnvioColor(envioId) {
    return ENVIO_COLORS[envioId % ENVIO_COLORS.length];
}

export function ItemCard({ item, envio }) {
    const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
        id: `item-${item.id}`,
        data: { type: 'item', item, envio },
    });

    const style = {
        transform: CSS.Translate.toString(transform),
        opacity: isDragging ? 0.5 : 1,
    };

    const color = getEnvioColor(envio.id);

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...listeners}
            {...attributes}
            className="kanban-item-card"
        >
            <div className="item-card-stripe" style={{ backgroundColor: color }} />
            <div className="item-card-content">
                <div className="item-card-header">
                    <span className="item-card-cantidad">{item.cantidad}x</span>
                    <span className="item-card-envio-code">{envio.codigo}</span>
                </div>
                <p className="item-card-descripcion">{item.descripcion}</p>
                <div className="item-card-footer">
                    <span className="item-card-cliente">{envio.cliente}</span>
                    {item.monto > 0 && (
                        <span className="item-card-monto">S/ {parseFloat(item.monto).toFixed(2)}</span>
                    )}
                </div>
            </div>
        </div>
    );
}
