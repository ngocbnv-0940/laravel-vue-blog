<template>
    <div class="container">
        <b-field :type="form.errors.has('title') ? 'is-danger' : null"
            :message="form.errors.get('title')">
            <b-input placeholder="Tiêu đề"
                required
                icon="header"
                v-model="form.title">
            </b-input>
        </b-field>
        <b-field grouped>
            <b-field :type="form.errors.has('category_id') ? 'is-danger' : null"
                :message="form.errors.get('category_id')">
                <b-select placeholder="Chọn một chuyên mục" icon="folder" required v-model="form.category_id">
                    <optgroup :label="category.name" v-for="(category, index) in categories" :key="index">
                        <option :value="childCat.id" v-for="childCat in category.childs" :key="childCat.id">{{ childCat.name }}</option>
                    </optgroup>
                </b-select>
            </b-field>
            <b-field expanded
                :type="form.errors.has('tags') ? 'is-danger' : null"
                :message="form.errors.get('tags')">
                <b-taginput
                    type="is-primary"
                    v-model="form.tags"
                    icon="tags"
                    placeholder="Thêm tag">
                </b-taginput>
            </b-field>
        </b-field>
        <b-field
            class="content"
            :type="form.errors.has('content') || form.errors.has('excerpt') ? 'is-danger' : null"
            :message="form.errors.has('excerpt') && !form.errors.has('content') ? 'Viết cái gì đi nào tôi ơi' : form.errors.get('content')">
            <markdown-editor
                :sanitize="true"
                v-model="form.content"
                :show-upload.sync="showUpload"
                :class="{ 'error-field': form.errors.has('content') || form.errors.has('excerpt') }">
            </markdown-editor>
        </b-field>
        <div class="has-text-centered">
            <a class="button is-outlined" @click="updatePost(false)">
                <b-icon icon="save"></b-icon>
                <span>Lưu nháp</span>
            </a>
            <a class="button is-primary is-outlined" @click="updatePost()">
                <b-icon icon="send-o"></b-icon>
                <span>Đăng bài</span>
            </a>
        </div>
    </div>
</template>
<script>
import MarkdownEditor from '~/components/MarkdownEditor'
import axios from 'axios'
import Form from 'vform'
import stripmd from '~/helpers/stripmd.js'
import { mapGetters } from 'vuex'
export default {
  data() {
    return {
      showUpload: false,
      form: new Form({
        title: '',
        category_id: null,
        excerpt: '',
        content: '',
        is_public: true,
        meta_keywords: '',
        tags: []
      }),
      slug: ''
    }
  },
  computed: mapGetters(['categories']),
  methods: {
    async updatePost(isPublic = true) {
      this.form.is_public = isPublic
      this.form.excerpt = this.getExcerpt(this.form.content, 155)
      this.form.meta_keywords = this.form.tags.toString()
      this.form.image = this.getImageFromContent(this.form.content)
      let { data: { slug, is_public }} = await this.form.patch(route('post.update', this.slug))
      this.$router.push({ name: 'post.show', params: { slug }})
      let notify = is_public ?
        'Bài viết của bạn đã được đăng/cập nhật thành công!' :
        'Lưu nháp/cập nhật thành công!'
      this.$snackbar.open(notify)
    },
    getImageFromContent(content) {
      let regex = /!\[.*?\]\((.*?)\)/
      return content.match(regex) ? content.match(regex)[1] : null
    },
    getExcerpt (content, maxLength) {
      let stripped = stripmd(content.trim()).replace(/(\r\n|\n|\r)/gm,' ')
      if (stripped.length > maxLength) {
        stripped = stripped.substr(0, maxLength) + '...'
      }
      return stripped
    },
    async fetchPost() {
      let { data: { data }} = await axios.get(route('post.show', this.$route.params.slug))
      this.slug = data.slug
      this.form.tags = data.tags.map(tag => tag.name)
      this.form.title = data.title
      this.form.category_id = data.category.id
      this.form.content = data.content
    }
  },
  created() {
    this.fetchPost()
  },
  components: {
    MarkdownEditor
  }
}
</script>
<style>
  @import '~simplemde/dist/simplemde.min.css';
  /*editor error*/
  .error-field {
    border: 1px solid transparent !important;
    border-color: red !important;
    border-radius: 3px !important;
  }
  /*fix margin*/
  .content pre:not(:last-child) {
    margin-bottom: 0;
  }
  /*translate status bar*/
  .editor-statusbar .lines:before {
      content: 'Số dòng: '
  }
  .editor-statusbar .words:before {
      content: 'Số từ: '
  }
</style>
