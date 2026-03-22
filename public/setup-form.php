<?php
require_once __DIR__ . '/partials/config.php';

$pdo = getDbConnection();
$stmt = $pdo->query('SELECT * FROM form ORDER BY id');
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($forms as &$form) {
  $decodedQuestions = json_decode($form['questions'], true);
  $form['questions'] = is_array($decodedQuestions) ? $decodedQuestions : [];
}
unset($form);
?>

<!DOCTYPE html>
<html class="h-full">
<?php include 'partials/meta.php'; ?>
<body class="antialiased bg-gray-50 dark:bg-gray-900 h-full">
<div class="max-w-[1600px] h-full bg-gray-200 dark:bg-gray-900 border-r border-gray-600 dark:border-gray-600">
	<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>
<main class="md:ml-64 h-auto pt-20">
<div class="p-8 border-t border-gray-600 dark:border-gray-600">
<section class="max-w-4xl w-full lg:w-5/8">
	<div class="<?= $theme->getHeaderClasses() ?>">
		<h1><?= htmlspecialchars($pageHeader) ?>, <?= htmlspecialchars($currentProfile['first_name'] ?? '') ?>.</h1>
	</div>
</section>

<div class="space-y-4">
  <?php foreach ($forms as $index => $form): ?>
  <div class="bg-gray-700 dark:bg-gray-700 rounded-lg">
    <button type="button" class="w-full flex items-center justify-between p-4 text-left" data-collapse-toggle="collapse<?= $form['id'] ?>">
      <div>
        <h3 class="text-lg font-bold text-gray-400"><?= htmlspecialchars($form['title']) ?></h3>
        <span class="text-sm text-gray-400">(<?= htmlspecialchars($form['slug']) ?>)</span>
      </div>
      <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 transition-transform"></i>
    </button>
    <div id="collapse<?= $form['id'] ?>" class="<?= $index === 0 ? '' : 'hidden' ?> p-4 border-t border-gray-600">
      <ul id="sortableHandle<?= $form['id'] ?>" class="space-y-2 mb-4">
        <?php foreach ($form['questions'] as $question): ?>
        <li class="flex items-center gap-3 p-3 bg-gray-600 rounded-lg" data-question-id="<?= htmlspecialchars($question['id']) ?>" data-form-id="<?= $form['id'] ?>">
          <span class="sortable-handle cursor-move">
            <i data-lucide="grip-vertical" class="w-5 h-5 text-gray-400"></i>
          </span>
          <span class="flex-1 text-gray-400 truncate"><?= htmlspecialchars($question['label']) ?></span>
          <div class="flex items-center gap-2">
            <span class="px-2 py-1 text-xs bg-gray-500 text-gray-300 rounded-full"><?= ucfirst($question['type']) ?></span>
            <?php if ($question['required']): ?>
            <span class="px-2 py-1 text-xs bg-blue-500 text-white rounded-full">Mandatory</span>
            <?php endif; ?>
            <button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-edit-question" data-form-id="<?= $form['id'] ?>" data-question-id="<?= htmlspecialchars($question['id']) ?>" data-question-label="<?= htmlspecialchars($question['label']) ?>" data-question-type="<?= htmlspecialchars($question['type']) ?>" data-question-required="<?= $question['required'] ? '1' : '0' ?>">
              <i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i>
            </button>
            <button type="button" class="p-2 hover:bg-gray-500 rounded-lg btn-delete-question" data-form-id="<?= $form['id'] ?>" data-question-id="<?= htmlspecialchars($question['id']) ?>">
              <i data-lucide="trash-2" class="w-4 h-4 text-gray-400"></i>
            </button>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="inline-flex items-center px-4 gap-2 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-full transition-colors btn-add-question" data-form-id="<?= $form['id'] ?>">
        <i data-lucide="plus" class="w-4 h-4 stroke-[2px]"></i> Add Question
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Edit Question Modal -->
<div id="editQuestionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
  <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
    <div class="flex items-center justify-between mb-4">
      <h3 id="editQuestionModalLabel" class="text-xl font-bold text-gray-400">Edit Question</h3>
      <button type="button" class="p-2 hover:bg-gray-700 rounded-lg modal-close">
        <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
      </button>
    </div>
    <form id="editQuestionForm" class="space-y-4">
      <input type="hidden" id="questionFormId">
      <input type="hidden" id="questionId">
      <div>
        <label for="questionText" class="block text-sm font-medium text-gray-400 mb-2">Question Text</label>
        <textarea id="questionText" rows="3" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none" placeholder="Enter question text"></textarea>
      </div>
      <div>
        <label for="questionType" class="block text-sm font-medium text-gray-400 mb-2">Input Type</label>
        <select id="questionType" class="w-full px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange text-gray-400 outline-none">
          <option value="text">Input</option>
          <option value="textarea">Textarea</option>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="questionMandatory" class="w-4 h-4 text-orange bg-gray-600 border-gray-600 rounded focus:ring-orange">
        <label for="questionMandatory" class="text-sm text-gray-400">Mandatory</label>
      </div>
    </form>
    <div class="flex gap-2 mt-6">
      <button type="button" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-gray-400 font-medium rounded-lg transition-colors modal-close">Cancel</button>
      <button type="button" id="saveQuestionBtn" class="flex-1 px-4 py-2 bg-orange hover:bg-orange/80 text-white font-medium rounded-lg transition-colors">Save Changes</button>
    </div>
  </div>
</div>

</div>
</main>

<?php include 'partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const editModal = document.getElementById('editQuestionModal');
  const modalTitle = document.getElementById('editQuestionModalLabel');
  const saveQuestionBtn = document.getElementById('saveQuestionBtn');
  
  // Modal open/close functionality
  function openModal(button) {
    const formId = button.getAttribute('data-form-id');
    const questionId = button.getAttribute('data-question-id');
    const questionLabel = button.getAttribute('data-question-label');
    const questionType = button.getAttribute('data-question-type');
    const questionRequired = button.getAttribute('data-question-required') === '1';
    const isEditMode = !!questionId;
    
    document.getElementById('questionFormId').value = formId;
    document.getElementById('questionId').value = questionId || '';
    document.getElementById('questionText').value = questionLabel || '';
    document.getElementById('questionType').value = questionType || 'text';
    document.getElementById('questionMandatory').checked = isEditMode ? questionRequired : false;
    modalTitle.textContent = isEditMode ? 'Edit Question' : 'Add Question';
    saveQuestionBtn.textContent = isEditMode ? 'Save Changes' : 'Add Question';
    
    editModal.classList.remove('hidden');
  }
  
  function closeModal() {
    editModal.classList.add('hidden');
  }
  
  // Open modal on edit/add button click
  document.querySelectorAll('.btn-edit-question, .btn-add-question').forEach(btn => {
    btn.addEventListener('click', function() {
      openModal(this);
    });
  });
  
  // Close modal buttons
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', closeModal);
  });
  
  // Close modal on backdrop click
  editModal.addEventListener('click', function(e) {
    if (e.target === editModal) {
      closeModal();
    }
  });
  
  // Accordion toggle with chevron rotation
  document.querySelectorAll('[data-collapse-toggle]').forEach(button => {
    button.addEventListener('click', function() {
      const targetId = this.getAttribute('data-collapse-toggle');
      const targetElement = document.getElementById(targetId);
      const chevron = this.querySelector('[data-lucide="chevron-down"]');
      
      if (targetElement.classList.contains('hidden')) {
        targetElement.classList.remove('hidden');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
      } else {
        targetElement.classList.add('hidden');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
      }
    });
    
    // Set initial chevron state
    const targetId = button.getAttribute('data-collapse-toggle');
    const targetElement = document.getElementById(targetId);
    const chevron = button.querySelector('[data-lucide="chevron-down"]');
    if (chevron && !targetElement.classList.contains('hidden')) {
      chevron.style.transform = 'rotate(180deg)';
    }
  });
  
  // Save changes
  saveQuestionBtn.addEventListener('click', function() {
    const formId = document.getElementById('questionFormId').value;
    const questionId = document.getElementById('questionId').value;
    const questionText = document.getElementById('questionText').value.trim();
    const questionType = document.getElementById('questionType').value;
    const questionMandatory = document.getElementById('questionMandatory').checked;
    const isEditMode = !!questionId;

    if (!questionText) {
      alert('Question text is required.');
      return;
    }

    const endpoint = isEditMode ? 'api/settings_forms_update.php' : 'api/settings_forms_add.php';
    const payload = {
      form_id: formId,
      label: questionText,
      type: questionType,
      required: questionMandatory
    };
    if (isEditMode) {
      payload.question_id = questionId;
    }
    
    fetch(endpoint, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert((isEditMode ? 'Error updating question: ' : 'Error adding question: ') + (data.error || 'Unknown error'));
      }
    })
    .catch(error => {
      alert('Error: ' + error.message);
    });
  });
  
  // Delete question
  document.querySelectorAll('.btn-delete-question').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      if (!confirm('Are you sure you want to delete this question?')) return;
      
      const formId = this.getAttribute('data-form-id');
      const questionId = this.getAttribute('data-question-id');
      
      fetch('api/settings_forms_delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          form_id: formId,
          question_id: questionId
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error deleting question: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        alert('Error: ' + error.message);
      });
    });
  });
  
  // Initialize sortable for each form
  document.querySelectorAll('[id^="sortableHandle"]').forEach(list => {
    new Sortable(list, {
      handle: '.sortable-handle',
      animation: 150,
      onEnd: function(evt) {
        const formId = evt.item.getAttribute('data-form-id');
        const questionOrder = [];
        
        evt.to.querySelectorAll('li').forEach((item, index) => {
          questionOrder.push({
            id: item.getAttribute('data-question-id'),
            order: index
          });
        });
        
        fetch('api/settings_forms_reorder.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            form_id: formId,
            questions: questionOrder
          })
        })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            alert('Error reordering questions: ' + (data.error || 'Unknown error'));
            location.reload();
          }
        })
        .catch(error => {
          alert('Error: ' + error.message);
          location.reload();
        });
      }
    });
  });
});
</script>
</div>
</body>
</html>