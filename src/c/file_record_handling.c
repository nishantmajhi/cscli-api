/* Unit 10: File Handling in C
 * Using a file to keep a record of students (fwrite/fread of a
 * structure) - matches the suggested lab exercise. */
#include <stdio.h>

struct Student {
    char name[50];
    int roll;
    float marks;
};

int main(void) {
    struct Student s1 = {"Aarav", 1, 87.5};

    FILE *fp = fopen("students.dat", "wb");
    if (fp == NULL) {
        printf("Error opening file!\n");
        return 1;
    }
    fwrite(&s1, sizeof(struct Student), 1, fp);
    fclose(fp);

    struct Student readBack;
    fp = fopen("students.dat", "rb");
    if (fp == NULL) {
        printf("Error opening file!\n");
        return 1;
    }
    fread(&readBack, sizeof(struct Student), 1, fp);
    fclose(fp);

    printf("Record read back: %s, roll %d, marks %.2f\n",
           readBack.name, readBack.roll, readBack.marks);

    return 0;
}
