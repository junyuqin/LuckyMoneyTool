<?php
/**
 * 知识点关联图页面
 * 展示知识点之间的关联关系，支持搜索和交互式查看
 */

// 定义访问许可
define('ACCESS_ALLOWED', true);

// 引入配置文件
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 启动会话
session_start();

// 设置页面标题
$page_title = '知识点关联图';

// 获取所有课程用于筛选
$courses = get_all_courses();

// 获取选中的课程ID
$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// 获取搜索关键词
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// 获取知识点数据
if ($selected_course_id > 0) {
    $knowledge_points = get_knowledge_points_by_course($selected_course_id);
} else {
    $knowledge_points = get_all_knowledge_points();
}

// 如果有搜索关键词，进行过滤
if (!empty($search_keyword)) {
    $filtered_points = [];
    foreach ($knowledge_points as $point) {
        if (stripos($point['name'], $search_keyword) !== false ||
            stripos($point['description'], $search_keyword) !== false) {
            $filtered_points[] = $point;
        }
    }
    $knowledge_points = $filtered_points;
}

// 获取知识点关联关系
$relations = get_knowledge_relations();

// 引入头部文件
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h2 class="page-title">🗺️ 知识点关联图</h2>
        <p class="page-description">
            可视化展示各专业课程的知识点之间的关联关系，帮助学生全面理解课程内容及其结构。
        </p>
    </div>
    
    <!-- 筛选和搜索区域 -->
    <div class="filter-section">
        <form method="get" action="" class="filter-form">
            <div class="form-group">
                <label for="course_filter">选择课程：</label>
                <select name="course_id" id="course_filter" onchange="this.form.submit()">
                    <option value="0">全部课程</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" 
                                <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="keyword_search">搜索知识点：</label>
                <input type="text" 
                       name="keyword" 
                       id="keyword_search" 
                       placeholder="输入关键词搜索..."
                       value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit" class="btn btn-primary">搜索</button>
                <?php if (!empty($search_keyword)): ?>
                    <a href="?course_id=<?php echo $selected_course_id; ?>" class="btn btn-outline">清除</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- 知识点图谱展示区域 -->
    <div class="knowledge-map-container">
        <div class="map-controls">
            <button class="btn btn-sm btn-outline" onclick="zoomIn()">🔍 放大</button>
            <button class="btn btn-sm btn-outline" onclick="zoomOut()">🔍 缩小</button>
            <button class="btn btn-sm btn-outline" onclick="resetZoom()">🔄 重置</button>
            <button class="btn btn-sm btn-outline" onclick="toggleLabels()">🏷️ 显示/隐藏标签</button>
        </div>
        
        <div id="knowledge-graph" class="knowledge-graph"></div>
        
        <div class="graph-legend">
            <h4>图例说明</h4>
            <div class="legend-item">
                <span class="legend-color core"></span>
                <span>核心知识点</span>
            </div>
            <div class="legend-item">
                <span class="legend-color basic"></span>
                <span>基础知识点</span>
            </div>
            <div class="legend-item">
                <span class="legend-color advanced"></span>
                <span>高级知识点</span>
            </div>
            <div class="legend-item">
                <span class="legend-line">→</span>
                <span>前置关系</span>
            </div>
        </div>
    </div>
    
    <!-- 知识点详情弹窗 -->
    <div id="point-detail-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div id="modal-body">
                <!-- 动态加载知识点详情 -->
            </div>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-value"><?php echo count($knowledge_points); ?></div>
            <div class="stat-label">知识点总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($relations); ?></div>
            <div class="stat-label">关联关系数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($courses); ?></div>
            <div class="stat-label">课程数量</div>
        </div>
    </div>
    
    <!-- 使用说明 -->
    <div class="info-section">
        <h3>📖 使用说明</h3>
        <ul class="info-list">
            <li><strong>点击查看：</strong>点击任意知识点节点，可查看该知识点的详细信息。</li>
            <li><strong>关联关系：</strong>箭头表示知识点之间的前置依赖关系，帮助理解学习顺序。</li>
            <li><strong>搜索功能：</strong>在搜索框中输入关键词，可快速定位相关知识点。</li>
            <li><strong>课程筛选：</strong>选择特定课程，只显示该课程相关的知识点。</li>
            <li><strong>缩放控制：</strong>使用放大、缩小按钮调整图谱显示比例。</li>
        </ul>
        <p class="info-note">
            💡 提示：知识点之间的关联关系可帮助学生更好地理解课程内容及其结构，
            建议按照推荐的学习路径逐步掌握各个知识点。
        </p>
    </div>
</div>

<script>
// 知识点数据
const knowledgePoints = <?php echo json_encode($knowledge_points, JSON_UNESCAPED_UNICODE); ?>;

// 关联关系数据
const relations = <?php echo json_encode($relations, JSON_UNESCAPED_UNICODE); ?>;

// D3.js 图谱配置
let svg, simulation, nodes, links;
let zoomLevel = 1;
let showLabels = true;

// 初始化图谱
function initGraph() {
    const container = document.getElementById('knowledge-graph');
    const width = container.clientWidth;
    const height = 600;
    
    // 创建 SVG
    svg = d3.select('#knowledge-graph')
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .call(d3.zoom().on('zoom', handleZoom));
    
    // 创建力导向模拟
    simulation = d3.forceSimulation()
        .force('link', d3.forceLink().id(d => d.id).distance(150))
        .force('charge', d3.forceManyBody().strength(-500))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collide', d3.forceCollide().radius(60));
    
    // 准备节点数据
    const nodeData = knowledgePoints.map(point => ({
        id: point.id,
        name: point.name,
        type: point.type || 'basic',
        description: point.description,
        course_id: point.course_id
    }));
    
    // 准备连接数据
    const linkData = relations.map(rel => ({
        source: rel.source_point_id,
        target: rel.target_point_id,
        type: rel.relation_type
    }));
    
    // 绘制连接线
    links = svg.append('g')
        .attr('class', 'links')
        .selectAll('line')
        .data(linkData)
        .enter()
        .append('line')
        .attr('stroke-width', 2)
        .attr('stroke', '#999');
    
    // 绘制节点组
    const nodeGroups = svg.append('g')
        .attr('class', 'nodes')
        .selectAll('g')
        .data(nodeData)
        .enter()
        .append('g')
        .call(d3.drag()
            .on('start', dragStarted)
            .on('drag', dragged)
            .on('end', dragEnded))
        .on('click', showPointDetail);
    
    // 绘制节点圆形
    nodeGroups.append('circle')
        .attr('r', 40)
        .attr('fill', d => getNodeColor(d.type))
        .attr('stroke', '#fff')
        .attr('stroke-width', 2);
    
    // 绘制节点标签
    nodeGroups.append('text')
        .attr('class', 'node-label')
        .attr('dy', 5)
        .attr('text-anchor', 'middle')
        .attr('font-size', '12px')
        .attr('fill', '#fff')
        .text(d => d.name.length > 6 ? d.name.substring(0, 6) + '...' : d.name);
    
    // 更新模拟
    simulation.nodes(nodeData).on('tick', ticked);
    simulation.force('link').links(linkData);
    
    function ticked() {
        links
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);
        
        nodeGroups.attr('transform', d => `translate(${d.x},${d.y})`);
    }
}

// 获取节点颜色
function getNodeColor(type) {
    const colors = {
        'core': '#e74c3c',
        'basic': '#3498db',
        'advanced': '#9b59b6'
    };
    return colors[type] || '#3498db';
}

// 缩放处理
function handleZoom(event) {
    svg.select('g.nodes').attr('transform', event.transform);
    svg.select('g.links').attr('transform', event.transform);
    zoomLevel = event.transform.k;
}

// 放大
function zoomIn() {
    svg.transition().call(d3.zoom().scaleBy(1.3));
}

// 缩小
function zoomOut() {
    svg.transition().call(d3.zoom().scaleBy(0.7));
}

// 重置缩放
function resetZoom() {
    svg.transition().call(d3.zoom().transform, d3.zoomIdentity);
}

// 切换标签显示
function toggleLabels() {
    showLabels = !showLabels;
    svg.selectAll('.node-label')
        .style('display', showLabels ? 'block' : 'none');
}

// 拖拽开始
function dragStarted(event, d) {
    if (!event.active) simulation.alphaTarget(0.3).restart();
    d.fx = d.x;
    d.fy = d.y;
}

// 拖拽中
function dragged(event, d) {
    d.fx = event.x;
    d.fy = event.y;
}

// 拖拽结束
function dragEnded(event, d) {
    if (!event.active) simulation.alphaTarget(0);
    d.fx = null;
    d.fy = null;
}

// 显示知识点详情
function showPointDetail(event, d) {
    event.stopPropagation();
    const modal = document.getElementById('point-detail-modal');
    const modalBody = document.getElementById('modal-body');
    
    // 获取关联的知识点
    const relatedPoints = relations.filter(r => 
        r.source_point_id === d.id || r.target_point_id === d.id
    );
    
    let relatedHtml = '';
    if (relatedPoints.length > 0) {
        relatedHtml = '<h4>关联知识点</h4><ul>';
        relatedPoints.forEach(rel => {
            const relatedPoint = knowledgePoints.find(p => 
                p.id === (rel.source_point_id === d.id ? rel.target_point_id : rel.source_point_id)
            );
            if (relatedPoint) {
                const relationText = rel.source_point_id === d.id ? 
                    '是其后置知识点' : '是其前置知识点';
                relatedHtml += `<li>${relatedPoint.name} - ${relationText}</li>`;
            }
        });
        relatedHtml += '</ul>';
    }
    
    modalBody.innerHTML = `
        <h3>${d.name}</h3>
        <p><strong>类型：</strong>${getTypeName(d.type)}</p>
        <p><strong>描述：</strong>${d.description || '暂无描述'}</p>
        ${relatedHtml}
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="addToLearningPath(${d.id})">
                加入学习路径
            </button>
            <button class="btn btn-outline" onclick="viewResources(${d.id})">
                查看相关资源
            </button>
        </div>
    `;
    
    modal.style.display = 'block';
}

// 获取类型名称
function getTypeName(type) {
    const names = {
        'core': '核心知识点',
        'basic': '基础知识点',
        'advanced': '高级知识点'
    };
    return names[type] || '普通知识点';
}

// 关闭弹窗
function closeModal() {
    document.getElementById('point-detail-modal').style.display = 'none';
}

// 加入学习路径
function addToLearningPath(pointId) {
    fetch('/knowledge_graph_system/api/add_to_path.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({point_id: pointId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('已成功加入学习路径', 'success');
        } else {
            showToast(data.message || '操作失败', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('网络错误', 'error');
    });
}

// 查看相关资源
function viewResources(pointId) {
    window.location.href = `/knowledge_graph_system/pages/resources.php?point_id=${pointId}`;
}

// 点击背景关闭弹窗
window.onclick = function(event) {
    const modal = document.getElementById('point-detail-modal');
    if (event.target === modal) {
        closeModal();
    }
};

// 移动端菜单切换
function toggleMobileMenu() {
    const sidebar = document.getElementById('mobileSidebar');
    sidebar.classList.toggle('active');
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initGraph();
});
</script>

<?php
// 引入底部文件
include '../includes/footer.php';
?>
