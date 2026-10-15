import { useDroppable } from '@dnd-kit/core';
import { ItemCard } from './ItemCard';

export function EnvioItemsColumn({ envio, items, onSplitClick, onMergeClick }) {
    const { setNodeRef, isOver } = useDroppable({
        id: 'unassigned',
        data: { type: 'unassigned' },
    });

    const sinAsignar = items.filter((i) => !i.transportista_id);

    return (
        <div className={`kanban-column kanban-column-unassigned ${isOver ? 'kanban-column-over' : ''}`}>
            <header className="kanban-column-header">
                <div className="kanban-column-title">
                    <h3>#{envio.codigo || envio.id}</h3>
                    <span className="kanban-column-subtitle">{envio.cliente}</span>
                </div>
                <span className="kanban-column-count">{sinAsignar.length}</span>
            </header>
            <div 
                ref={setNodeRef} 
                className="kanban-column-body"
                style={{ minHeight: '300px', display: 'flex', flexDirection: 'column', gap: '8px' }}
            >
                {sinAsignar.length === 0 ? (
                    <div 
                        className="kanban-column-empty"
                        style={{ pointerEvents: 'none', flexGrow: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                    >
                        Todos los items están asignados
                    </div>
                ) : (
                    sinAsignar.map((item) => (
                        <ItemCard 
                            key={item.id} 
                            item={item} 
                            envio={envio} 
                            onSplitClick={onSplitClick} 
                            onMergeClick={onMergeClick} 
                        />
                    ))
                )}
            </div>
        </div>
    );
}
