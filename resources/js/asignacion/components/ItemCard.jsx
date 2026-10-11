import { useDraggable, useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';

const ENVIO_COLORS = [
    '#c5a043', '#60a5fa', '#4ade80', '#f59e0b', '#ef6464',
    '#a78bfa', '#f472b6', '#34d399', '#fbbf24', '#fb923c',
];

function getEnvioColor(envioId) {
    if (!envioId) return ENVIO_COLORS[0];
    return ENVIO_COLORS[envioId % ENVIO_COLORS.length];
}

export function ItemCard({ item, envio, allItems = [], onSplitClick, onMergeClick }) {
    const { attributes, listeners, setNodeRef: setDragRef, transform, isDragging } = useDraggable({
        id: `item-${item?.id}`,
        data: { type: 'item', item, envio },
    });

    
    const { setNodeRef: setDropRef, isOver } = useDroppable({
        id: `item-drop-${item?.id}`,
        data: { type: 'item', item, envio },
    });

    if (!item || !envio) return null;


    const style = {
        transform: CSS.Translate.toString(transform),
        opacity: isDragging ? 0.4 : 1,
    };

    const color = getEnvioColor(envio.id);
    const canSplit = item.cantidad > 1 && typeof onSplitClick === 'function';

    const normalize = (str) => (str || '').trim().toLowerCase();

    const todosLosItems = Array.isArray(envio?.items) ? envio.items : [];

    const hermanoCompatible = todosLosItems.find(
        (otro) => otro && otro.id !== item.id && normalize(otro.descripcion) === normalize(item.descripcion)
    );

    const canMerge = Boolean(hermanoCompatible && typeof onMergeClick === 'function');

    const handleContextMenu = (e) => {
        if (!canSplit) return;
        e.preventDefault();
        e.stopPropagation();
        onSplitClick(item, envio);
    };

    const handleFastMerge = (e) => {
        e.stopPropagation();
        if (canMerge && hermanoCompatible) {
            onMergeClick(item.id, hermanoCompatible.id);
        }
    };

    return (
        <div ref={setDropRef} className="kanban-item-drop-target" style={{ width: '100%' }}>
            <div
                ref={setDragRef}
                style={style}
                {...listeners}
                {...attributes}
                onContextMenu={handleContextMenu}
                className={`kanban-item-card ${isOver && !isDragging ? 'item-card-drop-hover' : ''}`}
            >
                <div className="item-card-stripe" style={{ backgroundColor: color }} />
                <div className="item-card-content">
                    <div className="item-card-header">
                        <span className="item-card-cantidad">{item.cantidad}x</span>
                        <span className="item-card-envio-code">{envio.codigo}</span>

                        <div className="item-card-actions">
                            {canMerge && (
                                <button
                                    type="button"
                                    className="btn-card-action btn-merge-item"
                                    title={`Unir con otra fracción de "${item.descripcion}"`}
                                    onPointerDown={(e) => e.stopPropagation()}
                                    onClick={handleFastMerge}
                                >
                                    <span className="material-icon">call_merge</span>
                                </button>
                            )}

                            {canSplit && (
                                <button
                                    type="button"
                                    className="btn-card-action btn-split-item"
                                    title="Dividir / Partir carga (o anticlic)"
                                    onPointerDown={(e) => e.stopPropagation()}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        onSplitClick(item, envio);
                                    }}
                                >
                                    <span className="material-icon">call_split</span>
                                </button>
                            )}
                        </div>
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
        </div>
    );
}
