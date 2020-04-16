'use strict'
let pageLock          = null
window.onbeforeunload = isPageLocked

/**
 * Return value of pageLock
 *
 * @returns {null|boolean}
 */
function isPageLocked () {
  if (pageLock) return pageLock

  return null
}

/**
 *
 * @param status
 * @param messageSelector
 */
function lockPage (status) {
  if (!status) status = null
  pageLock = status
  // const element = document.querySelector(messageSelector)
  // if (element) element.innerHTML = status ? '☒︎' : ''
}

/**
 * Creating a new content block
 *
 * @param event
 */
function createNewBlock (event) {
  event.preventDefault()
  // search for root block element
  const parent = event.target.closest('.js__wp-block')
  const select = parent.querySelector('.js__block-type')
  const offset = parseFloat(getComputedStyle(select).height)
  // add new block after selected block
  addBlock({
    templateId: 'template__wp-block',
    element: parent,
    focusOn: '.js__block-content',
    scrollOffset: -offset,
  })
  // reindex all blocks
  indexDynamicFields('.js__wp-block')
}

/**
 * Function that submits form data to server.
 *
 * @param form
 */
function submit (form) {
  const localization = window[form.id.replace('-', '')]
  let submits        = form.querySelectorAll('.js__submit')
  if (isRequiredEmpty(form)) {
    submits.forEach(function (submit) {
      setState(submit, 'wait')
    })

    echo('.js__messages', 'Fields that marked red should be filed', 30, 'li')

    setTimeout(function () {
      submits.forEach(function (submit) {
        setState(submit, 'produce')
      })
    }, 300)

    return
  }

  submits.forEach(function (submit) {
    submit.toggleClass('error', false)
    setState(submit, 'wait')
  })

  let data = form.serialize()

  get_content({
    method: 'post',
    url: localization['ajaxUrl'],
    data: data,
  }).then(function (result) {
    result = JSON.parse(result)

    if (true === result.success) {

      result    = result.data
      let title = document.title.replace('* ', '')
      setUrl({ title: title, html: '' }, '?post_id=' + result.ID)

      lockPage(false)

      // common fields correction
      let names = ['ID', 'tags_input', 'post_title',]
      names.forEach((name) => {
        let control = form.querySelector('[name=' + name + ']')
        if (control) {
          if ('tags_input' === name) {
            control.value = result[name].join(', ')
          } else {
            control.value = result[name]
          }

        }
      })

      // if content blocks exists
      if (result['block_content']) {
        result['block_content'].forEach(function (value, i) {
          form.querySelector('[name="block_content[' + i + ']"]').value = result['block_content'][i] ? stripSlashes(result['block_content'][i]) : ''
        })
      }

      echo('.js__messages', 'Post has been saved', 5, 'li')

      countText('.js__form', '.js__block-content', '.js__part')
    } else {

      if (result.data.errors) {
        for (let error of result.data.errors) {
          echo('.js__messages', error, 5, 'li')
        }
      } else {
        echo('.js__messages', 'There was an error while saving', 10, 'li')
      }
    }
    submits.forEach(function (submit) {
      setState(submit, 'produce')
    })
  }).catch(function (err) {
    if (err.hasOwnProperty('statusText')) {
      let errorMessage = err.statusText + ', status: ' + err.status + '.'
      echo('.js__messages', 'There was an error while saving. Check filling correction of all fields then try again. ' + errorMessage, 30, 'li')
    } else {
      console.error(err)
    }
  })
}

// reindex all blocks
indexDynamicFields('.js__wp-block')

on('change', '#myTheme-Post [value]', () => lockPage(true))

// Listen for click on a plus button
on('click', '.js__add-wp-block', (event) => {
  createNewBlock(event)
})

// Listen for click on a plus button
on('keydown keyup', '.js__block-content', (event) => {
  resizeTextarea(event.target)
  if (13 === event.keyCode) {
    createNewBlock(event)
  }
})

// Listen for click on a plus button
on('change', '.js__block-type', (event) => {
  event.preventDefault()
  const select = event.target
  // search for root block element
  const parent = select.closest('.js__wp-block')
  if ('remove' === select.value) {
    // remove selected block
    parent.remove()
    // reindex all blocks
    indexDynamicFields('.js__wp-block')
  } else {
    parent.querySelector('.js__block-options').value = select.options[select.selectedIndex].getAttribute('data-options')
  }
})

on('focus click', '.js__block-content', (event) => {
  const blockContent = event.target
  const block        = blockContent.closest('.js__wp-block')
  const blockType    = block.querySelector('.js__block-type')
  const add          = block.querySelector('.js__add')

  document.querySelectorAll('.js__block-type').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  document.querySelectorAll('.js__block-content').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  document.querySelectorAll('.js__add').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  blockContent.toggleClass('active', false)

  blockType.toggleClass('active', true)
  add.toggleClass('active', true)
  blockContent.toggleClass('active', true)
})

on('click', '.js__submit', function (event) {
  event.preventDefault()
  const form = event.target.closest('form')
  submit(form)
})

on('submit', '.js__form', function (event) {
  event.preventDefault()
  const form = event.target
  submit(form)
})

